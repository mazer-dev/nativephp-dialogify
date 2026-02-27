// resources/android/src/ToastFunctions.kt
package dev.mazer.dialogify

import android.os.Handler
import android.os.Looper
import android.util.Log
import android.view.ViewGroup
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeError
import com.nativephp.mobile.bridge.BridgeFunction
import dev.mazer.dialogify.ui.CustomToastView
import org.json.JSONObject

object ToastFunctions {

    class Show(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val message = parameters["message"] as? String
                ?: throw BridgeError.InvalidParameters("message is required")

            val position = parameters["position"] as? String ?: "bottom"
            val backgroundColor = parameters["backgroundColor"] as? String
            val textColor = parameters["textColor"] as? String
            val icon = parameters["icon"] as? String
            val cornerRadius = (parameters["cornerRadius"] as? Number)?.toInt()
            val id = parameters["id"] as? String

            val durationMs = when (val dur = parameters["duration"]) {
                is String -> when (dur.lowercase()) {
                    "short" -> 2000L
                    else -> 4000L
                }
                is Number -> dur.toLong()
                else -> 4000L
            }

            // Parse action button
            val actionMap = parameters["action"]
            var actionLabel: String? = null
            var actionId: String? = null
            var actionColor: String? = null

            if (actionMap is Map<*, *>) {
                actionLabel = actionMap["label"] as? String
                actionId = actionMap["id"] as? String
                actionColor = actionMap["color"] as? String
            } else if (actionMap is JSONObject) {
                actionLabel = actionMap.optString("label", null)
                actionId = actionMap.optString("id", null)
                actionColor = actionMap.optString("color", null)
            }

            val event = "MazerDev\\Dialogify\\Events\\ToastAction"

            Log.d("Dialogify.Toast", "Showing toast: $message (position=$position)")

            return try {
                val rootView = activity.findViewById<ViewGroup>(android.R.id.content)
                    ?: throw BridgeError.ExecutionFailed("Could not find root view")

                val toast = CustomToastView(
                    context = activity,
                    rootView = rootView,
                    message = message,
                    position = position,
                    bgColor = backgroundColor,
                    txtColor = textColor,
                    iconName = icon,
                    durationMs = durationMs,
                    cornerRadiusDp = cornerRadius,
                    actionLabel = actionLabel,
                    actionColor = actionColor,
                    onAction = if (actionId != null && actionLabel != null) {
                        {
                            val payload = JSONObject().apply {
                                put("buttonId", actionId)
                                put("label", actionLabel)
                                if (id != null) put("toastId", id)
                            }
                            dispatchEvent(activity, event, payload.toString())
                        }
                    } else null,
                )

                toast.show()

                mapOf("success" to true)
            } catch (e: BridgeError) {
                throw e
            } catch (e: Exception) {
                Log.e("Dialogify.Toast", "Error: ${e.message}", e)
                throw BridgeError.ExecutionFailed("Failed to show toast: ${e.message}")
            }
        }

        private fun dispatchEvent(activity: FragmentActivity, event: String, payloadJson: String) {
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
                    val webViewProvider = activity as? com.nativephp.mobile.bridge.WebViewProvider
                    webViewProvider?.getWebView()?.evaluateJavascript(js, null)
                } catch (e: Exception) {
                    Log.e("Dialogify.Toast", "Event dispatch error: ${e.message}", e)
                }
            }
        }
    }
}
