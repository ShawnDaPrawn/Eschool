import 'dart:math';

import 'package:eschool_core/src/layout/breakpoints.dart';
import 'package:flutter/material.dart';

/// Keeps a screen's content to a phone-like column on tablets and desktops.
///
/// On a phone (narrower than [Breakpoints.tablet]) it returns [child] as it
/// is, so nothing changes there. Wider, it centres [child] in a column at most
/// [maxWidth] wide on the page background, and tells the child its width is
/// the column's: the existing screens size things as a share of
/// `MediaQuery.sizeOf(context).width`, and keep their proportions that way.
class ContentColumn extends StatelessWidget {
  const ContentColumn({
    required this.child,
    this.maxWidth = Breakpoints.contentMaxWidth,
    super.key,
  });

  final Widget child;
  final double maxWidth;

  @override
  Widget build(BuildContext context) {
    final MediaQueryData media = MediaQuery.of(context);
    return LayoutBuilder(
      builder: (context, constraints) {
        final double available = constraints.hasBoundedWidth
            ? constraints.maxWidth
            : media.size.width;
        if (available < Breakpoints.tablet || available <= maxWidth) {
          if (available == media.size.width) {
            return child;
          }
          // Inside a rail layout: the child gets the width it really has.
          return MediaQuery(
            data: media.copyWith(size: Size(available, media.size.height)),
            child: child,
          );
        }
        final double width = min(available, maxWidth);
        return ColoredBox(
          color: Theme.of(context).colorScheme.surfaceContainerLow,
          child: Center(
            child: SizedBox(
              width: width,
              child: MediaQuery(
                data: media.copyWith(size: Size(width, media.size.height)),
                child: child,
              ),
            ),
          ),
        );
      },
    );
  }
}
