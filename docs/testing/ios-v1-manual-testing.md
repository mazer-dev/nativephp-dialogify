# iOS v1.0 Manual Testing Checklist

## Prerequisites
- iOS 15+ simulator or device
- NativePHP app with dialogify plugin installed

## Toast Tests

- [ ] Basic toast: `Dialogify::toast('Hello')->show()`
- [ ] Position top: `->position(Position::Top)`
- [ ] Position center: `->position(Position::Center)`
- [ ] Position bottom (default): `->position(Position::Bottom)`
- [ ] Custom colors: `->backgroundColor('#FF0000')->textColor('#FFFFFF')`
- [ ] With icon: `->icon(Icon::Info)`
- [ ] Custom duration short: `->duration(Duration::Short)` — dismisses in 2s
- [ ] Custom duration ms: `->duration(6000)` — dismisses in 6s
- [ ] Corner radius: `->cornerRadius(20)`
- [ ] Action button: `->action(Button::make('undo', 'Undo'))` — verify ToastAction event fires
- [ ] Action dismisses toast immediately
- [ ] Fade in animation (0.2s)
- [ ] Fade out animation (0.2s)
- [ ] Multiple toasts don't overlap (second replaces first)

## Alert Tests — Simple (UIAlertController)

- [ ] Basic alert: `Dialogify::alert('Title', 'Message')->positive(Button::make('ok', 'OK'))->show()`
- [ ] Two buttons: positive + negative
- [ ] Three buttons: positive + negative + neutral
- [ ] Cancelable dismiss (tap outside) fires ButtonPressed with `buttonId: "cancelled"`
- [ ] Non-cancelable: `->cancelable(false)` — cannot dismiss without button
- [ ] Text input: `->addInput(Input::make('name', 'Name'))` — verify InputSubmitted payload
- [ ] Password input: type password, dots shown
- [ ] Number input: numeric keyboard shown
- [ ] Email input: email keyboard shown
- [ ] Custom event class: `->event('App\\Events\\MyEvent')`
- [ ] Dialog ID in payload: `->id('my-alert')`

## Alert Tests — Complex (Form Modal)

- [ ] Date input: UIDatePicker shown, format "yyyy-MM-dd"
- [ ] Time input: UIDatePicker time mode, format "HH:mm"
- [ ] DateTime input: UIDatePicker dateAndTime mode, format "yyyy-MM-dd HH:mm"
- [ ] Date with min/max: `->minDate('2024-01-01')->maxDate('2026-12-31')`
- [ ] Time with minuteInterval: `->minuteInterval(15)`
- [ ] Select input: UIPickerView with options
- [ ] Radio input: same as select (UIPickerView)
- [ ] Checkbox input: UISwitch toggles, array of selected keys in payload
- [ ] Mixed inputs: text + date + select in same alert
- [ ] Default values pre-populated for all input types
- [ ] Cancel dismiss fires ButtonPressed with `buttonId: "cancelled"`

## Sheet Tests

- [ ] Basic sheet: `Dialogify::sheet('Title')->addButton(Button::make('ok', 'OK'))->show()`
- [ ] Multiple buttons
- [ ] With inputs: same input types as Alert complex mode
- [ ] Cancelable: swipe down to dismiss fires cancel event
- [ ] Non-cancelable: `->cancelable(false)` — no swipe dismiss
- [ ] Grabber visible at top
- [ ] Sheet resizable (medium/large detents)
- [ ] Dialog ID in payload

## Event Dispatch

- [ ] Verify Livewire `#[On('native:MazerDev\\Dialogify\\Events\\ButtonPressed')]` receives event
- [ ] Verify payload includes all expected fields (buttonId, label, index/inputs, dialogId)
- [ ] Verify checkbox inputs arrive as array of strings
