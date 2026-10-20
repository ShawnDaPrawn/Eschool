import 'package:flutter/material.dart';

class CustomRefreshIndicator extends StatelessWidget {
  const CustomRefreshIndicator({
    required this.child, required this.displacement, required this.onRefreshCallback, super.key,
  });
  final Widget child;
  final Function onRefreshCallback;
  final double displacement;

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      displacement: displacement,
      onRefresh: () async {
        onRefreshCallback();
      },
      child: child,
    );
  }
}
