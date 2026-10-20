import 'package:flutter/material.dart';

class TabBackgroundContainer extends StatelessWidget {
  const TabBackgroundContainer({required this.boxConstraints, super.key});
  final BoxConstraints boxConstraints;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(5),
      decoration: BoxDecoration(
        border: Border.all(color: Theme.of(context).scaffoldBackgroundColor),
        borderRadius: BorderRadius.circular(15),
      ),
      margin: EdgeInsets.only(
        left: boxConstraints.maxWidth * 0.1,
        right: boxConstraints.maxWidth * 0.1,
        top: boxConstraints.maxHeight * 0.3,
      ),
      height: boxConstraints.maxHeight * 0.325,
      width: boxConstraints.maxWidth * 0.375,
      child: Container(
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: BorderRadius.circular(10),
        ),
      ),
    );
  }
}
