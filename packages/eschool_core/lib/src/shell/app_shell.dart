import 'package:eschool_core/src/models/study_material.dart';
import 'package:flutter/widgets.dart';

/// What the shared widgets need from the app they run in: the few things the
/// learner app and the teacher app still do differently. Each app puts one
/// above its navigator (in `MaterialApp.builder`), so every screen, dialog and
/// bottom sheet can read it.
class AppShell extends InheritedWidget {
  const AppShell({
    required this.errorMessageKeyFromCode,
    required this.openStudyMaterial,
    required this.studyMaterialButton,
    required this.bottomNavigationHeightPercentage,
    required super.child,
    super.key,
  });

  /// The label key for an API or app error code. The codes mean different
  /// things in each app (113 is "not in this session year" for a learner and
  /// "name already taken" for a teacher), so each app maps its own.
  final String Function(String errorCode) errorMessageKeyFromCode;

  /// Opens a file, link or video a teacher attached: the app's viewer for PDFs
  /// and images, the browser for videos, a download otherwise.
  final void Function(BuildContext context, StudyMaterial studyMaterial)
  openStudyMaterial;

  /// The small round button drawn next to an attachment's name.
  final Widget Function(BuildContext context, StudyMaterial studyMaterial)
  studyMaterialButton;

  /// The bottom bar's height as a share of the screen height, so lists above
  /// it can leave room for it.
  final double bottomNavigationHeightPercentage;

  /// The nearest [AppShell]. Every app installs one; a widget test that pumps
  /// a shared widget on its own must install one too.
  static AppShell of(BuildContext context) {
    final AppShell? shell = context
        .dependOnInheritedWidgetOfExactType<AppShell>();
    assert(shell != null, 'No AppShell above this widget.');
    return shell!;
  }

  @override
  bool updateShouldNotify(AppShell oldWidget) =>
      errorMessageKeyFromCode != oldWidget.errorMessageKeyFromCode ||
      openStudyMaterial != oldWidget.openStudyMaterial ||
      studyMaterialButton != oldWidget.studyMaterialButton ||
      bottomNavigationHeightPercentage !=
          oldWidget.bottomNavigationHeightPercentage;
}
