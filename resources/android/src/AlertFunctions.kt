// resources/android/src/AlertFunctions.kt
package dev.mazer.dialogify

import android.app.DatePickerDialog
import android.app.TimePickerDialog
import android.os.Handler
import android.os.Looper
import android.text.InputType
import android.util.Log
import android.view.ViewGroup
import android.widget.*
import androidx.appcompat.app.AlertDialog
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeError
import com.nativephp.mobile.bridge.BridgeFunction
import org.json.JSONArray
import org.json.JSONObject
import java.util.Calendar
import java.lang.ref.WeakReference

object AlertFunctions {

    class Show(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val title = parameters["title"] as? String ?: ""
            val message = parameters["message"] as? String
            val icon = parameters["icon"] as? String
            val cancelable = parameters["cancelable"] as? Boolean ?: true
            val id = parameters["id"] as? String
            val customEvent = parameters["event"] as? String

            val positive = parseButton(parameters["positive"])
            val negative = parseButton(parameters["negative"])
            val neutral = parseButton(parameters["neutral"])

            val inputs = parseInputs(parameters["inputs"])

            val hasInputs = inputs.isNotEmpty()
            val buttonEvent = customEvent ?: if (hasInputs) {
                "MazerDev\\Dialogify\\Events\\InputSubmitted"
            } else {
                "MazerDev\\Dialogify\\Events\\ButtonPressed"
            }

            // activity is captured strongly here; this is safe because the dialog's lifetime
            // is bounded to the activity's lifetime. dispatchEvent() uses WeakReference internally.
            Handler(Looper.getMainLooper()).post {
                try {
                    var buttonPressed = false

                    val builder = AlertDialog.Builder(activity)
                        .setTitle(title)
                        .setCancelable(cancelable)

                    if (message != null) builder.setMessage(message)

                    // Icon
                    if (icon != null) {
                        val iconRes = activity.resources.getIdentifier(
                            "ic_$icon", "drawable", activity.packageName
                        ).takeIf { it != 0 }
                            ?: activity.resources.getIdentifier(icon, "drawable", activity.packageName)
                        if (iconRes != 0) builder.setIcon(iconRes)
                    }

                    // Input fields view
                    val inputViews = mutableMapOf<String, Any>()
                    if (hasInputs) {
                        val container = LinearLayout(activity).apply {
                            orientation = LinearLayout.VERTICAL
                            val pad = (24 * resources.displayMetrics.density).toInt()
                            setPadding(pad, (8 * resources.displayMetrics.density).toInt(), pad, 0)
                        }

                        for (input in inputs) {
                            val inputId = input.getString("id")
                            val label = input.getString("label")
                            val type = input.optString("type", "text")
                            val placeholder = input.optString("placeholder", null as String?)
                            val defaultValue = input.opt("defaultValue")

                            val labelView = TextView(activity).apply {
                                text = label
                                val lp = LinearLayout.LayoutParams(
                                    ViewGroup.LayoutParams.MATCH_PARENT,
                                    ViewGroup.LayoutParams.WRAP_CONTENT
                                ).apply {
                                    topMargin = (8 * resources.displayMetrics.density).toInt()
                                }
                                layoutParams = lp
                            }
                            container.addView(labelView)

                            when (type) {
                                "text", "number", "password", "email" -> {
                                    val editText = EditText(activity).apply {
                                        hint = placeholder
                                        inputType = when (type) {
                                            "number" -> InputType.TYPE_CLASS_NUMBER
                                            "password" -> InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_PASSWORD
                                            "email" -> InputType.TYPE_TEXT_VARIATION_EMAIL_ADDRESS
                                            else -> InputType.TYPE_CLASS_TEXT
                                        }
                                        if (defaultValue is String) setText(defaultValue)
                                        if (defaultValue is Number) setText(defaultValue.toString())
                                        val maxLen = input.optInt("maxLength", 0)
                                        if (maxLen > 0) {
                                            filters = arrayOf(android.text.InputFilter.LengthFilter(maxLen))
                                        }
                                    }
                                    container.addView(editText)
                                    inputViews[inputId] = editText
                                }

                                "date" -> {
                                    val dateButton = Button(activity).apply {
                                        text = (defaultValue as? String) ?: "Select date"
                                        setOnClickListener {
                                            val cal = Calendar.getInstance()
                                            DatePickerDialog(activity, { _, y, m, d ->
                                                val dateStr = String.format("%04d-%02d-%02d", y, m + 1, d)
                                                this.text = dateStr
                                                this.tag = dateStr
                                            }, cal.get(Calendar.YEAR), cal.get(Calendar.MONTH), cal.get(Calendar.DAY_OF_MONTH)).show()
                                        }
                                        tag = defaultValue as? String
                                    }
                                    container.addView(dateButton)
                                    inputViews[inputId] = dateButton
                                }

                                "time" -> {
                                    val timeButton = Button(activity).apply {
                                        text = (defaultValue as? String) ?: "Select time"
                                        setOnClickListener {
                                            val cal = Calendar.getInstance()
                                            TimePickerDialog(activity, { _, h, m ->
                                                val timeStr = String.format("%02d:%02d", h, m)
                                                this.text = timeStr
                                                this.tag = timeStr
                                            }, cal.get(Calendar.HOUR_OF_DAY), cal.get(Calendar.MINUTE), true).show()
                                        }
                                        tag = defaultValue as? String
                                    }
                                    container.addView(timeButton)
                                    inputViews[inputId] = timeButton
                                }

                                "select", "radio" -> {
                                    val options = parseOptions(input.optJSONObject("options"))
                                    val spinner = Spinner(activity).apply {
                                        adapter = ArrayAdapter(activity, android.R.layout.simple_spinner_dropdown_item, options.values.toList())
                                        if (defaultValue is String) {
                                            val idx = options.keys.indexOf(defaultValue)
                                            if (idx >= 0) setSelection(idx)
                                        }
                                    }
                                    container.addView(spinner)
                                    inputViews[inputId] = Pair(spinner, options.keys.toList())
                                }

                                "checkbox" -> {
                                    val options = parseOptions(input.optJSONObject("options"))
                                    val defaults = parseDefaults(input.opt("defaultValue"))
                                    val checkboxes = mutableMapOf<String, CheckBox>()

                                    for ((key, optLabel) in options) {
                                        val cb = CheckBox(activity).apply {
                                            text = optLabel
                                            isChecked = key in defaults
                                        }
                                        container.addView(cb)
                                        checkboxes[key] = cb
                                    }
                                    inputViews[inputId] = checkboxes
                                }
                            }
                        }

                        builder.setView(container)
                    }

                    // Buttons
                    fun onButtonClick(btn: JSONObject, index: Int) {
                        buttonPressed = true
                        val buttonId = btn.getString("id")
                        val buttonLabel = btn.getString("label")

                        if (hasInputs) {
                            val values = collectInputValues(inputViews, inputs)
                            val payload = JSONObject().apply {
                                put("buttonId", buttonId)
                                put("label", buttonLabel)
                                if (id != null) put("dialogId", id)
                                put("inputs", JSONObject(values))
                            }
                            dispatchEvent(activity, buttonEvent, payload.toString())
                        } else {
                            val payload = JSONObject().apply {
                                put("buttonId", buttonId)
                                put("label", buttonLabel)
                                put("index", index)
                                if (id != null) put("dialogId", id)
                            }
                            dispatchEvent(activity, buttonEvent, payload.toString())
                        }
                    }

                    if (positive != null) {
                        builder.setPositiveButton(positive.getString("label")) { _, _ ->
                            onButtonClick(positive, 0)
                        }
                    }

                    if (negative != null) {
                        builder.setNegativeButton(negative.getString("label")) { _, _ ->
                            onButtonClick(negative, 1)
                        }
                    }

                    if (neutral != null) {
                        builder.setNeutralButton(neutral.getString("label")) { _, _ ->
                            onButtonClick(neutral, 2)
                        }
                    }

                    builder.setOnCancelListener {
                        if (!buttonPressed) {
                            val payload = JSONObject().apply {
                                put("buttonId", "cancelled")
                                put("label", "Cancelled")
                                put("index", -1)
                                if (id != null) put("dialogId", id)
                            }
                            dispatchEvent(activity, "MazerDev\\Dialogify\\Events\\ButtonPressed", payload.toString())
                        }
                    }

                    builder.show()
                } catch (e: Exception) {
                    Log.e("Dialogify.Alert", "Error: ${e.message}", e)
                }
            }

            return emptyMap()
        }

        @Suppress("UNCHECKED_CAST")
        private fun parseButton(param: Any?): JSONObject? {
            return when (param) {
                is Map<*, *> -> JSONObject(param as Map<String, Any>)
                is JSONObject -> param
                else -> null
            }
        }

        @Suppress("UNCHECKED_CAST")
        private fun parseInputs(param: Any?): List<JSONObject> {
            val result = mutableListOf<JSONObject>()
            when (param) {
                is JSONArray -> {
                    for (i in 0 until param.length()) {
                        result.add(param.getJSONObject(i))
                    }
                }
                is List<*> -> {
                    param.filterIsInstance<Map<*, *>>().forEach {
                        result.add(JSONObject(it as Map<String, Any>))
                    }
                }
            }
            return result
        }

        private fun parseOptions(json: JSONObject?): LinkedHashMap<String, String> {
            val map = LinkedHashMap<String, String>()
            if (json != null) {
                json.keys().forEach { key -> map[key] = json.getString(key) }
            }
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

        @Suppress("UNCHECKED_CAST")
        private fun collectInputValues(
            inputViews: Map<String, Any>,
            inputs: List<JSONObject>
        ): Map<String, Any?> {
            val result = mutableMapOf<String, Any?>()

            for (input in inputs) {
                val inputId = input.getString("id")
                val type = input.optString("type", "text")
                val view = inputViews[inputId] ?: continue

                result[inputId] = when (type) {
                    "text", "number", "password", "email" -> (view as EditText).text.toString()
                    "date", "time" -> (view as Button).tag as? String ?: ""
                    "select", "radio" -> {
                        val (spinner, keys) = view as Pair<Spinner, List<String>>
                        keys.getOrNull(spinner.selectedItemPosition)
                    }
                    "checkbox" -> {
                        val checkboxes = view as Map<String, CheckBox>
                        checkboxes.filter { it.value.isChecked }.keys.toList()
                    }
                    else -> null
                }
            }

            return result
        }

        private fun dispatchEvent(activity: FragmentActivity, event: String, payloadJson: String) {
            val weakActivity = WeakReference(activity)
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
                    val act = weakActivity.get() ?: return@post
                    val webViewProvider = act as? com.nativephp.mobile.bridge.WebViewProvider
                    webViewProvider?.getWebView()?.evaluateJavascript(js, null)
                } catch (e: Exception) {
                    Log.e("Dialogify.Alert", "Event dispatch error: ${e.message}", e)
                }
            }
        }
    }
}
