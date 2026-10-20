import 'package:eschool_teacher/utils/assets.dart';
import 'package:flutter/material.dart';
import 'package:lottie/lottie.dart';

class FloatingActionAddButton extends StatelessWidget {
  const FloatingActionAddButton({required this.onTap, super.key});
  final Function onTap;

  @override
  Widget build(BuildContext context) {
    return FloatingActionButton(
      elevation: 4.5,
      onPressed: () {
        onTap();
      },
      backgroundColor: Theme.of(context).colorScheme.primary,
      child: Padding(
        padding: const EdgeInsets.all(13.5),
        child: Lottie.asset(
          Assets.addFloatingAnimation,
          animate: true,
          repeat: true,
        ),
      ),
    );
  }
}
