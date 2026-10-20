import 'package:flutter/material.dart';

class EditButton extends StatelessWidget {
  const EditButton({required this.onTap, super.key});
  final Function onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        onTap.call();
      },
      child: Icon(
        Icons.edit,
        size: 22.5,
        color: Theme.of(context).colorScheme.onPrimary,
      ),
    );
  }
}
