import 'package:flutter/material.dart';

class CustomCloseButton extends StatelessWidget {
  const CustomCloseButton({required this.onTapCloseButton, super.key});
  final Function onTapCloseButton;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(5),
      onTap: () {
        onTapCloseButton();
      },
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(5),
          border: Border.all(color: Theme.of(context).colorScheme.secondary),
        ),
        width: 25,
        height: 25,
        child: Icon(
          Icons.close,
          size: 20,
          color: Theme.of(context).colorScheme.secondary,
        ),
      ),
    );
  }
}
