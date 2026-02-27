// resources/android/src/ui/CustomToastView.kt
package dev.mazer.dialogify.ui

import android.animation.Animator
import android.animation.AnimatorListenerAdapter
import android.animation.ObjectAnimator
import android.content.Context
import android.graphics.Color
import android.graphics.drawable.GradientDrawable
import android.os.Handler
import android.os.Looper
import android.util.TypedValue
import android.view.Gravity
import android.view.View
import android.view.ViewGroup
import android.widget.FrameLayout
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.TextView

class CustomToastView(
    private val context: Context,
    private val rootView: ViewGroup,
    private val message: String,
    private val position: String = "bottom",
    private val bgColor: String? = null,
    private val txtColor: String? = null,
    private val iconName: String? = null,
    private val durationMs: Long = 4000L,
    private val cornerRadiusDp: Int? = null,
    private val actionLabel: String? = null,
    private val actionColor: String? = null,
    private val onAction: (() -> Unit)? = null,
) {
    private var container: FrameLayout? = null

    fun show() {
        Handler(Looper.getMainLooper()).post {
            val density = context.resources.displayMetrics.density

            // Container card
            val card = LinearLayout(context).apply {
                orientation = LinearLayout.HORIZONTAL
                gravity = Gravity.CENTER_VERTICAL
                val paddingH = (16 * density).toInt()
                val paddingV = (12 * density).toInt()
                setPadding(paddingH, paddingV, paddingH, paddingV)

                val bg = GradientDrawable().apply {
                    setColor(parseColor(bgColor, "#323232"))
                    cornerRadius = (cornerRadiusDp ?: 8) * density
                }
                background = bg
                elevation = 6 * density
            }

            // Icon
            if (iconName != null) {
                val iconResId = getIconResource(context, iconName)
                if (iconResId != 0) {
                    val icon = ImageView(context).apply {
                        setImageResource(iconResId)
                        setColorFilter(parseColor(txtColor, "#FFFFFF"))
                        val size = (20 * density).toInt()
                        layoutParams = LinearLayout.LayoutParams(size, size).apply {
                            marginEnd = (8 * density).toInt()
                        }
                    }
                    card.addView(icon)
                }
            }

            // Message
            val textView = TextView(context).apply {
                text = message
                setTextColor(parseColor(txtColor, "#FFFFFF"))
                setTextSize(TypedValue.COMPLEX_UNIT_SP, 14f)
                layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f)
            }
            card.addView(textView)

            // Action button
            if (actionLabel != null) {
                val actionView = TextView(context).apply {
                    text = actionLabel.uppercase()
                    setTextColor(parseColor(actionColor, "#BB86FC"))
                    setTextSize(TypedValue.COMPLEX_UNIT_SP, 14f)
                    isFocusable = true
                    isClickable = true
                    val lp = LinearLayout.LayoutParams(
                        LinearLayout.LayoutParams.WRAP_CONTENT,
                        LinearLayout.LayoutParams.WRAP_CONTENT
                    ).apply {
                        marginStart = (12 * density).toInt()
                    }
                    layoutParams = lp
                    setOnClickListener {
                        onAction?.invoke()
                        dismiss()
                    }
                }
                card.addView(actionView)
            }

            // Wrapper for positioning
            val wrapper = FrameLayout(context).apply {
                val margin = (16 * density).toInt()
                val bottomNav = (64 * density).toInt()
                val lp = FrameLayout.LayoutParams(
                    FrameLayout.LayoutParams.MATCH_PARENT,
                    FrameLayout.LayoutParams.WRAP_CONTENT
                ).apply {
                    gravity = when (position) {
                        "top" -> Gravity.TOP or Gravity.CENTER_HORIZONTAL
                        "center" -> Gravity.CENTER
                        else -> Gravity.BOTTOM or Gravity.CENTER_HORIZONTAL
                    }
                    setMargins(margin, margin + if (position == "top") (48 * density).toInt() else 0, margin, if (position == "bottom") bottomNav else margin)
                }
                layoutParams = lp
                addView(card)
            }

            container = wrapper

            // Fade in
            wrapper.alpha = 0f
            rootView.addView(wrapper)
            ObjectAnimator.ofFloat(wrapper, "alpha", 0f, 1f).apply {
                duration = 200
                start()
            }

            // Auto dismiss
            Handler(Looper.getMainLooper()).postDelayed({ dismiss() }, durationMs)
        }
    }

    private fun dismiss() {
        container?.let { view ->
            ObjectAnimator.ofFloat(view, "alpha", 1f, 0f).apply {
                duration = 200
                addListener(object : AnimatorListenerAdapter() {
                    override fun onAnimationEnd(animation: Animator) {
                        (view.parent as? ViewGroup)?.removeView(view)
                    }
                })
                start()
            }
            container = null
        }
    }

    companion object {
        fun parseColor(hex: String?, default: String): Int {
            return try {
                Color.parseColor(hex ?: default)
            } catch (e: Exception) {
                Color.parseColor(default)
            }
        }

        fun getIconResource(context: Context, name: String): Int {
            // Try Material icon by name
            return context.resources.getIdentifier(
                "ic_${name}", "drawable", context.packageName
            ).takeIf { it != 0 }
                ?: context.resources.getIdentifier(name, "drawable", context.packageName)
        }
    }
}
