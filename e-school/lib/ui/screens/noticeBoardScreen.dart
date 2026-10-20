import 'package:eschool/ui/widgets/noticeBoardContainer.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

class NoticeBoardScreen extends StatelessWidget {
  const NoticeBoardScreen({super.key, this.childId});
  final int? childId;

  static Route route(RouteSettings routeSettings) {
    return CupertinoPageRoute(builder: (_) => const NoticeBoardScreen());
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: NoticeBoardContainer(
        showBackButton: true,
      ),
    );
  }
}
