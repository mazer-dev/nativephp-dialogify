## Dialogify — Native Dialogs for NativePHP Mobile

Dialogify provides native toast notifications, alert dialogs, and bottom sheets with form inputs, custom styling, and event-driven responses.

No permissions required. No configuration files. The service provider auto-registers via Laravel package discovery.

### Facade

Use `MazerDev\Dialogify\Facades\Dialogify` to create dialogs:

@verbatim
<code-snippet name="Toast notification" lang="php">
use MazerDev\Dialogify\Facades\Dialogify;
use MazerDev\Dialogify\Enums\Position;
use MazerDev\Dialogify\Enums\Duration;
use MazerDev\Dialogify\Enums\Icon;
use MazerDev\Dialogify\Button;

// Simple — auto-shows when variable goes out of scope
Dialogify::toast('Record saved!');

// Customized
Dialogify::toast('Operation complete')
    ->position(Position::Top)
    ->backgroundColor('#1a1a2e')
    ->textColor('#ffffff')
    ->icon(Icon::CheckCircle)
    ->duration(Duration::Long)
    ->cornerRadius(12)
    ->id('save-toast')
    ->show();

// With action button
Dialogify::toast('Item deleted')
    ->action(Button::make('undo', 'Undo'))
    ->show();
</code-snippet>

<code-snippet name="Alert dialog" lang="php">
use MazerDev\Dialogify\Facades\Dialogify;
use MazerDev\Dialogify\Button;
use MazerDev\Dialogify\Input;
use MazerDev\Dialogify\Enums\Icon;
use MazerDev\Dialogify\Enums\InputType;

// Simple
Dialogify::alert('Warning', 'Are you sure?');

// With buttons
Dialogify::alert('Delete Record', 'This action cannot be undone.')
    ->positive(Button::make('confirm', 'Delete')->destructive())
    ->negative(Button::make('cancel', 'Cancel')->cancel())
    ->icon(Icon::Warning)
    ->cancelable(false)
    ->id('delete-alert')
    ->show();

// With form inputs
Dialogify::alert('Edit Profile')
    ->addInput(Input::make('name', 'Name')->placeholder('Enter name')->required())
    ->addInput(Input::make('email', 'Email')->type(InputType::Email))
    ->positive(Button::make('save', 'Save')->primary())
    ->event(MyCustomEvent::class)
    ->show();
</code-snippet>

<code-snippet name="Bottom sheet" lang="php">
use MazerDev\Dialogify\Facades\Dialogify;
use MazerDev\Dialogify\Button;
use MazerDev\Dialogify\Input;
use MazerDev\Dialogify\Enums\InputType;

// With buttons
Dialogify::sheet('Choose Action')
    ->message('Select what you want to do')
    ->addButton(Button::make('edit', 'Edit')->primary())
    ->addButton(Button::make('share', 'Share'))
    ->addButton(Button::make('delete', 'Delete')->destructive())
    ->cancelable(true)
    ->id('action-sheet')
    ->show();

// With form inputs
Dialogify::sheet('Feedback')
    ->addInput(
        Input::make('rating', 'Rating')
            ->type(InputType::Select)
            ->options(['5' => 'Excellent', '4' => 'Good', '3' => 'Average'])
    )
    ->addInput(Input::make('comment', 'Comment')->placeholder('Your feedback'))
    ->addButton(Button::make('submit', 'Submit')->primary())
    ->show();
</code-snippet>
@endverbatim

### Facade Methods

- `Dialogify::toast(string $message, array $options = [])` returns `PendingToast`
- `Dialogify::alert(string $title, ?string $message = null, array $options = [])` returns `PendingAlert`
- `Dialogify::sheet(?string $title = null, ?string $message = null)` returns `PendingSheet`

### PendingToast Methods

- `position(Position $position)` — Top, Bottom, Center
- `backgroundColor(string $hex)` — background color
- `textColor(string $hex)` — text color
- `icon(Icon|string $icon)` — icon enum or string
- `duration(Duration|int $duration)` — Short (2s), Long (3.5s), or custom ms
- `cornerRadius(int $dp)` — corner radius in dp
- `action(Button $button)` — action button
- `id(string $id)` — toast identifier
- `show()` — display immediately (optional, auto-shows on destruct)

### PendingAlert Methods

- `positive(Button $button)` — positive/confirm button
- `negative(Button $button)` — negative/cancel button
- `neutral(Button $button)` — neutral button
- `icon(Icon|string $icon)` — icon enum or string
- `cancelable(bool $cancelable)` — allow dismiss by tapping outside (default: true)
- `addInput(Input $input)` — add form input field
- `id(string $id)` — dialog identifier
- `event(string $eventClass)` — custom event class for responses
- `show()` — display immediately (optional, auto-shows on destruct)

### PendingSheet Methods

- `message(string $message)` — sheet description
- `addButton(Button $button)` — add button (unlimited)
- `addInput(Input $input)` — add form input field
- `cancelable(bool $cancelable)` — allow dismiss by swiping down (default: true)
- `id(string $id)` — sheet identifier
- `event(string $eventClass)` — custom event class for responses
- `show()` — display immediately (optional, auto-shows on destruct)

### Button

- `Button::make(string $id, string $label)` — create button
- `->primary()` — primary style
- `->destructive()` — destructive/danger style
- `->neutral()` — neutral style
- `->cancel()` — cancel style
- `->color(string $hex)` — text color
- `->backgroundColor(string $hex)` — background color

### Input

- `Input::make(string $id, string $label)` — create input
- `->type(InputType $type)` — Text, Number, Password, Email, Date, Time, DateTime, Select, Checkbox, Radio
- `->placeholder(string $text)` — placeholder text
- `->defaultValue(mixed $value)` — default value
- `->required(bool $required = true)` — mark as required
- `->maxLength(int $max)` — max character length
- `->options(array $options)` — options for Select, Checkbox, Radio (key => label pairs)
- `->minDate(string $date)` — min date for Date/DateTime
- `->maxDate(string $date)` — max date for Date/DateTime
- `->minuteInterval(int $minutes)` — minute interval for Time/DateTime

### Enums

- `Position`: Top, Bottom, Center
- `Duration`: Short, Long
- `ButtonStyle`: Primary, Destructive, Neutral, Cancel
- `InputType`: Text, Number, Password, Email, Date, Time, DateTime, Select, Checkbox, Radio
- `Icon`: CheckCircle, Warning, Error, Info, Check, Delete, Close, QuestionMark

### Events

Dialogify dispatches these events when users interact with dialogs. Listen with `#[OnNative]` or standard Laravel listeners.

- `MazerDev\Dialogify\Events\ButtonPressed` — alert/sheet button pressed
  - `string $buttonId`, `string $label`, `int $index`, `?string $dialogId`
- `MazerDev\Dialogify\Events\ToastAction` — toast action button tapped
  - `string $buttonId`, `string $label`, `?string $toastId`
- `MazerDev\Dialogify\Events\InputSubmitted` — form dialog submitted
  - `string $buttonId`, `string $label`, `?string $dialogId`, `array $inputs` (key => value)

@verbatim
<code-snippet name="Listening to events with #[OnNative]" lang="php">
use NativePHP\Attributes\OnNative;
use MazerDev\Dialogify\Events\ButtonPressed;
use MazerDev\Dialogify\Events\InputSubmitted;

#[OnNative(ButtonPressed::class)]
public function handleButton(ButtonPressed $event): void
{
    if ($event->buttonId === 'confirm') {
        // Handle confirmation
    }
}

#[OnNative(InputSubmitted::class)]
public function handleInput(InputSubmitted $event): void
{
    $name = $event->inputs['name'] ?? '';
    // Process submitted input
}
</code-snippet>

<code-snippet name="Listening to events with Laravel listener" lang="php">
use Illuminate\Support\Facades\Event;
use MazerDev\Dialogify\Events\ToastAction;

Event::listen(ToastAction::class, function (ToastAction $event) {
    if ($event->buttonId === 'undo') {
        // Handle undo action
    }
});
</code-snippet>
@endverbatim

### JavaScript Bridge Functions

For SPA frameworks (Inertia + Vue/React), use bridge calls directly:

- `Dialogify.Toast` — show toast notification
- `Dialogify.Alert` — show alert dialog
- `Dialogify.Sheet` — show bottom sheet

@verbatim
<code-snippet name="JavaScript bridge call example" lang="js">
window.__nativephp.bridgeCall('Dialogify.Toast', JSON.stringify({
    message: 'Hello from JS',
    position: 'bottom',
    duration: 'long',
}));
</code-snippet>
@endverbatim

### Best Practices

- Use `->id()` on dialogs to identify them in event handlers when multiple dialogs are active.
- Use `->event(CustomEvent::class)` to dispatch your own event class instead of the defaults.
- Toast auto-shows when the `PendingToast` goes out of scope — calling `->show()` is optional.
- Alert and Sheet also auto-show on destruct, but `->show()` makes intent explicit.
- Button styles map to platform conventions: `primary` = filled, `destructive` = red, `cancel` = dismiss.
- Icon enum values map automatically to Material Icons (Android) and SF Symbols (iOS).
