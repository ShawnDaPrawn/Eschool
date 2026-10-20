import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

class CustomCupertinoSwitch extends StatelessWidget {

  const CustomCupertinoSwitch({
    required this.onChanged, required this.value, super.key,
  });
  final Function onChanged;
  final bool value;

  @override
  Widget build(BuildContext context) {
    return Transform.scale(
      scale: 0.65,
      child: CupertinoSwitch(
        activeTrackColor: Theme.of(context).colorScheme.primary,
        value: value,
        onChanged: (value) {
          onChanged(value);
        },
      ),
    );
  }
}
