// resources/android/src/SheetFunctions.kt
package dev.mazer.dialogify

import android.app.DatePickerDialog
import android.app.TimePickerDialog
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.text.InputType
import android.util.Log
import android.view.Gravity
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.*
import androidx.fragment.app.FragmentActivity
import com.google.android.material.bottomsheet.BottomSheetDialogFragment
import com.nativephp.mobile.bridge.BridgeFunction
import org.json.JSONArray
import org.json.JSONObject
import java.lang.ref.WeakReference
import java.util.Calendar
import java.util.Locale

object SheetFunctions {

    class Show(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val title = parameters["title"] as? String
            val message = parameters["message"] as? String
            val cancelable = parameters["cancelable"] as? Boolean ?: true
            val id = parameters["id"] as? String
            val customEvent = parameters["event"] as? String

            val buttons = parseArray(parameters["buttons"])
            val inputs = parseArray(parameters["inputs"])
            val hasInputs = inputs.isNotEmpty()

            val event = customEvent ?: if (hasInputs) {
                "MazerDev\\Dialogify\\Events\\InputSubmitted"
            } else {
                "MazerDev\\Dialogify\\Events\\ButtonPressed"
            }

            // activity is captured strongly here; this is safe because the fragment's lifetime
            // is bounded to the activity's lifetime. dispatchEvent() uses WeakReference internally.
            Handler(Looper.getMainLooper()).post {
                try {
                    val fragment = DialogifySheetFragment.newInstance(
                        title, message, buttons, inputs, cancelable, id, event, hasInputs
                    )
                    fragment.weakActivity = WeakReference(activity)
                    fragment.show(activity.supportFragmentManager, "dialogify_sheet")
                } catch (e: Exception) {
                    Log.e("Dialogify.Sheet", "Error: ${e.message}", e)
                }
            }

            return emptyMap()
        }

        @Suppress("UNCHECKED_CAST")
        private fun parseArray(param: Any?): List<JSONObject> {
            val result = mutableListOf<JSONObject>()
            when (param) {
                is JSONArray -> {
                    for (i in 0 until param.length()) result.add(param.getJSONObject(i))
                }
                is List<*> -> {
                    param.filterIsInstance<Map<*, *>>().forEach {
                        result.add(JSONObject(it as Map<String, Any>))
                    }
                }
            }
            return result
        }
    }

    class DialogifySheetFragment : BottomSheetDialogFragment() {
        var weakActivity: WeakReference<FragmentActivity>? = null
        private var title: String? = null
        private var message: String? = null
        private var buttons = listOf<JSONObject>()
        private var inputs = listOf<JSONObject>()
        private var sheetCancelable = true
        private var dialogId: String? = null
        private var event: String = ""
        private var hasInputs = false
        private var buttonPressed = false

        private val inputViews = mutableMapOf<String, Any>()

        companion object {
            fun newInstance(
                title: String?, message: String?,
                buttons: List<JSONObject>, inputs: List<JSONObject>,
                cancelable: Boolean, id: String?, event: String, hasInputs: Boolean
            ): DialogifySheetFragment {
                return DialogifySheetFragment().apply {
                    this.title = title
                    this.message = message
                    this.buttons = buttons
                    this.inputs = inputs
                    this.sheetCancelable = cancelable
                    this.dialogId = id
                    this.event = event
                    this.hasInputs = hasInputs
                }
            }
        }

        override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
            val density = resources.displayMetrics.density
            val pad = (24 * density).toInt()

            val layout = LinearLayout(requireContext()).apply {
                orientation = LinearLayout.VERTICAL
                setPadding(pad, pad, pad, pad)
            }

            // Title
            if (title != null) {
                layout.addView(TextView(requireContext()).apply {
                    text = title
                    textSize = 20f
                    gravity = Gravity.CENTER
                    val lp = LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT)
                    lp.bottomMargin = (8 * density).toInt()
                    layoutParams = lp
                })
            }

            // Message
            if (message != null) {
                layout.addView(TextView(requireContext()).apply {
                    text = message
                    textSize = 14f
                    val lp = LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT)
                    lp.bottomMargin = (16 * density).toInt()
                    layoutParams = lp
                })
            }

            // Inputs
            for (input in inputs) {
                val inputId = input.getString("id")
                val label = input.getString("label")
                val type = input.optString("type", "text")
                val placeholder = input.optString("placeholder", null as String?)
                val defaultValue = input.opt("defaultValue")

                layout.addView(TextView(requireContext()).apply {
                    text = label
                    val lp = LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT)
                    lp.topMargin = (8 * density).toInt()
                    layoutParams = lp
                })

                when (type) {
                    "text", "number", "password", "email" -> {
                        val editText = EditText(requireContext()).apply {
                            hint = placeholder
                            inputType = when (type) {
                                "number" -> InputType.TYPE_CLASS_NUMBER
                                "password" -> InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_PASSWORD
                                "email" -> InputType.TYPE_TEXT_VARIATION_EMAIL_ADDRESS
                                else -> InputType.TYPE_CLASS_TEXT
                            }
                            if (defaultValue is String) setText(defaultValue)
                            val maxLen = input.optInt("maxLength", 0)
                            if (maxLen > 0) filters = arrayOf(android.text.InputFilter.LengthFilter(maxLen))
                        }
                        layout.addView(editText)
                        inputViews[inputId] = editText
                    }
                    "date" -> {
                        val btn = Button(requireContext()).apply {
                            text = (defaultValue as? String) ?: "Select date"
                            tag = defaultValue as? String
                            setOnClickListener {
                                val cal = Calendar.getInstance()
                                DatePickerDialog(requireContext(), { _, y, m, d ->
                                    val s = String.format(Locale.getDefault(), "%04d-%02d-%02d", y, m + 1, d)
                                    this.text = s
                                    this.tag = s
                                }, cal.get(Calendar.YEAR), cal.get(Calendar.MONTH), cal.get(Calendar.DAY_OF_MONTH)).show()
                            }
                        }
                        layout.addView(btn)
                        inputViews[inputId] = btn
                    }
                    "time" -> {
                        val btn = Button(requireContext()).apply {
                            text = (defaultValue as? String) ?: "Select time"
                            tag = defaultValue as? String
                            setOnClickListener {
                                val cal = Calendar.getInstance()
                                TimePickerDialog(requireContext(), { _, h, m ->
                                    val s = String.format(Locale.getDefault(), "%02d:%02d", h, m)
                                    this.text = s
                                    this.tag = s
                                }, cal.get(Calendar.HOUR_OF_DAY), cal.get(Calendar.MINUTE), true).show()
                            }
                        }
                        layout.addView(btn)
                        inputViews[inputId] = btn
                    }
                    "select", "radio" -> {
                        val options = parseOptions(input.optJSONObject("options"))
                        val spinner = Spinner(requireContext()).apply {
                            adapter = ArrayAdapter(requireContext(), android.R.layout.simple_spinner_dropdown_item, options.values.toList())
                            if (defaultValue is String) {
                                val idx = options.keys.indexOf(defaultValue)
                                if (idx >= 0) setSelection(idx)
                            }
                        }
                        layout.addView(spinner)
                        inputViews[inputId] = Pair(spinner, options.keys.toList())
                    }
                    "checkbox" -> {
                        val options = parseOptions(input.optJSONObject("options"))
                        val defaults = parseDefaults(input.opt("defaultValue"))
                        val cbs = mutableMapOf<String, CheckBox>()
                        for ((key, l) in options) {
                            val cb = CheckBox(requireContext()).apply { text = l; isChecked = key in defaults }
                            layout.addView(cb)
                            cbs[key] = cb
                        }
                        inputViews[inputId] = cbs
                    }
                }
            }

            // Buttons
            for ((index, btn) in buttons.withIndex()) {
                val buttonId = btn.getString("id")
                val buttonLabel = btn.getString("label")

                layout.addView(Button(requireContext()).apply {
                    text = buttonLabel
                    val lp = LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT)
                    lp.topMargin = (4 * density).toInt()
                    layoutParams = lp

                    setOnClickListener {
                        buttonPressed = true
                        if (hasInputs) {
                            val values = collectValues()
                            val payload = JSONObject().apply {
                                put("buttonId", buttonId)
                                put("label", buttonLabel)
                                if (dialogId != null) put("dialogId", dialogId)
                                put("inputs", JSONObject(values))
                            }
                            dispatchEvent(payload.toString())
                        } else {
                            val payload = JSONObject().apply {
                                put("buttonId", buttonId)
                                put("label", buttonLabel)
                                put("index", index)
                                if (dialogId != null) put("dialogId", dialogId)
                            }
                            dispatchEvent(payload.toString())
                        }
                        dismiss()
                    }
                })
            }

            dialog?.setCancelable(sheetCancelable)
            dialog?.setCanceledOnTouchOutside(sheetCancelable)

            return ScrollView(requireContext()).apply { addView(layout) }
        }

        override fun onCancel(dialog: android.content.DialogInterface) {
            super.onCancel(dialog)
            // Fire cancel event if no button was pressed (user dismissed via back press or outside tap)
            if (!buttonPressed) {
                val payload = JSONObject().apply {
                    put("buttonId", "cancelled")
                    put("label", "Cancelled")
                    put("index", -1)
                    if (dialogId != null) put("dialogId", dialogId)
                }
                dispatchEvent(payload.toString())
            }
        }

        @Suppress("UNCHECKED_CAST")
        private fun collectValues(): Map<String, Any?> {
            val result = mutableMapOf<String, Any?>()
            for (input in inputs) {
                val id = input.getString("id")
                val type = input.optString("type", "text")
                val view = inputViews[id] ?: continue
                result[id] = when (type) {
                    "text", "number", "password", "email" -> (view as EditText).text.toString()
                    "date", "time" -> (view as Button).tag as? String ?: ""
                    "select", "radio" -> {
                        val (spinner, keys) = view as Pair<Spinner, List<String>>
                        keys.getOrNull(spinner.selectedItemPosition)
                    }
                    "checkbox" -> {
                        val cbs = view as Map<String, CheckBox>
                        cbs.filter { it.value.isChecked }.keys.toList()
                    }
                    else -> null
                }
            }
            return result
        }

        private fun parseOptions(json: JSONObject?): LinkedHashMap<String, String> {
            val map = LinkedHashMap<String, String>()
            json?.keys()?.forEach { key -> map[key] = json.getString(key) }
            return map
        }

        private fun parseDefaults(value: Any?): Set<String> {
            return when (value) {
                is JSONArray -> (0 until value.length()).map { value.getString(it) }.toSet()
                is List<*> -> value.filterIsInstance<String>().toSet()
                is String -> setOf(value)
                else -> emptySet()
            }
        }

        private fun dispatchEvent(payloadJson: String) {
            val eventForJs = event.replace("\\", "\\\\")
            val js = """
                (function () {
                    const payload = $payloadJson;
                    const detail = { event: "$eventForJs", payload };
                    document.dispatchEvent(new CustomEvent("native-event", { detail }));
                    if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                        window.Livewire.dispatch("native:$eventForJs", payload);
                    }
                    fetch('/_native/api/events', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ event: "$eventForJs", payload: payload })
                    }).catch(e => console.error("Dialogify event error:", e.message));
                })();
            """.trimIndent()

            Handler(Looper.getMainLooper()).post {
                try {
                    val act = weakActivity?.get() ?: return@post
                    val webViewProvider = act as? com.nativephp.mobile.bridge.WebViewProvider
                    webViewProvider?.getWebView()?.evaluateJavascript(js, null)
                } catch (e: Exception) {
                    Log.e("Dialogify.Sheet", "Event dispatch error: ${e.message}", e)
                }
            }
        }
    }
}
