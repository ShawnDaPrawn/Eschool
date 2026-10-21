import 'package:flutter/material.dart';

class CustomSwitch extends StatelessWidget {
  const CustomSwitch({required this.onChanged, required this.value, super.key});
  final Function onChanged;
  final bool value;

  @override
  Widget build(BuildContext context) {
    return Transform.scale(
      scale: 0.65,
      child: Switch(
        value: value,
        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
        onChanged: (value) {
          onChanged(value);
        },
      ),
    );
  }
}
