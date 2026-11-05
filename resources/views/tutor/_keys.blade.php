{{-- A row of maths keys that type into the field with id $for. Works with mouse, touch and keyboard. --}}
<div class="math-keys" role="group" aria-label="Maths keys">
    @foreach (['x' => 'x', '=' => '=', '(' => '(', ')' => ')', '+' => '+', '−' => '−', '×' => '×', '÷' => '÷', '/' => 'fraction /'] as $key => $label)
        <button type="button" class="math-key" data-insert="{{ $key }}" data-target="{{ $for }}" aria-label="Type {{ $label }}">{{ $key }}</button>
    @endforeach
</div>
@once
<script>
document.addEventListener('click', function (e) {
    var b = e.target.closest('.math-key');
    if (!b) return;
    var f = document.getElementById(b.dataset.target);
    if (!f) return;
    var s = f.selectionStart ?? f.value.length, t = f.selectionEnd ?? f.value.length;
    f.value = f.value.slice(0, s) + b.dataset.insert + f.value.slice(t);
    f.focus();
    f.selectionStart = f.selectionEnd = s + b.dataset.insert.length;
});
</script>
@endonce
