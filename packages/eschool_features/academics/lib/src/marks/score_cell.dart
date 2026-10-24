import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'mark_input.dart';

class ScoreCell extends StatefulWidget {
  const ScoreCell({
    required this.value,
    required this.maximum,
    required this.label,
    required this.onChanged,
    required this.onPaste,
    required this.onEnter,
    required this.onFocused,
    required this.register,
    super.key,
  });
  final String value, maximum, label;
  final ValueChanged<String> onChanged, onPaste;
  final VoidCallback onEnter, onFocused;
  final void Function(FocusNode, bool) register;
  @override
  State<ScoreCell> createState() => _ScoreCellState();
}

class _ScoreCellState extends State<ScoreCell> {
  late final TextEditingController _text = TextEditingController(
    text: widget.value,
  );
  late final FocusNode _focus = FocusNode(onKeyEvent: _key);
  KeyEventResult _key(FocusNode node, KeyEvent event) {
    if (event is KeyDownEvent &&
        event.logicalKey == LogicalKeyboardKey.keyV &&
        (HardwareKeyboard.instance.isControlPressed ||
            HardwareKeyboard.instance.isMetaPressed)) {
      Clipboard.getData(Clipboard.kTextPlain).then((data) {
        if (mounted && data?.text != null) widget.onPaste(data!.text!);
      });
      return KeyEventResult.handled;
    }
    return KeyEventResult.ignored;
  }

  @override
  void initState() {
    super.initState();
    widget.register(_focus, true);
    _focus.addListener(() {
      if (_focus.hasFocus) widget.onFocused();
    });
  }

  @override
  void didUpdateWidget(covariant ScoreCell oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (_text.text != widget.value) {
      _text.value = TextEditingValue(
        text: widget.value,
        selection: TextSelection.collapsed(offset: widget.value.length),
      );
    }
  }

  @override
  void dispose() {
    widget.register(_focus, false);
    _text.dispose();
    _focus.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    String? error;
    try {
      markUnits(widget.value, markUnits(widget.maximum, 100000000)!);
    } on FormatException {
      error = '0 – ${widget.maximum}';
    }
    return TextField(
      controller: _text,
      focusNode: _focus,
      keyboardType: const TextInputType.numberWithOptions(decimal: true),
      textInputAction: TextInputAction.next,
      onEditingComplete: () {},
      onSubmitted: (_) => widget.onEnter(),
      onChanged: widget.onChanged,
      decoration: InputDecoration(
        labelText: widget.label,
        errorText: error,
        isDense: true,
        border: const OutlineInputBorder(),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 10,
          vertical: 13,
        ),
      ),
    );
  }
}
