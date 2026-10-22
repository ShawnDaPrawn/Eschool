import 'package:eschool_core/src/layout/content_column.dart';
import 'package:flutter/cupertino.dart';

/// The page route every screen of both apps opens with: a
/// [CupertinoPageRoute] (the slide the apps have always used) whose page is
/// kept to a [ContentColumn] on tablets and desktops. On a phone the page is
/// exactly what [builder] returns.
///
/// A screen that lays itself out for every width (the home screens, through
/// `AdaptiveScaffold`) passes `contentColumn: false`.
class AdaptivePageRoute<T> extends CupertinoPageRoute<T> {
  AdaptivePageRoute({
    required super.builder,
    super.settings,
    super.maintainState,
    super.fullscreenDialog,
    this.contentColumn = true,
  });

  final bool contentColumn;

  @override
  Widget buildContent(BuildContext context) {
    final Widget page = super.buildContent(context);
    return contentColumn ? ContentColumn(child: page) : page;
  }
}
