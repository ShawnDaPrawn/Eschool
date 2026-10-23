import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/src/context/me_context.dart';
import 'package:eschool_session/src/context/me_context_cubit.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Old servers keep their existing navigation; a configured server is the
/// authority on availability. The backend enforces the same check on every call.
bool allowsSchoolModule(MeContext? context, String key, {bool write = false}) {
  if (context == null || !context.isFeatureOn('module_switches')) return true;
  return context.isModuleOn(key) && (!write || !context.isModuleReadOnly(key));
}

/// Hides a navigation item or a write action, and follows context refreshes.
class ModuleVisibility extends StatelessWidget {
  const ModuleVisibility({
    required this.module,
    required this.child,
    this.write = false,
    super.key,
  });

  final String module;
  final Widget child;
  final bool write;

  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) =>
            allowsSchoolModule(state.context, module, write: write)
            ? child
            : const SizedBox.shrink(),
      );
}

/// A direct link must not build a disabled feature or start its network calls.
class ModuleAccessPage extends StatelessWidget {
  const ModuleAccessPage({
    required this.module,
    required this.builder,
    this.write = false,
    super.key,
  });

  final String module;
  final WidgetBuilder builder;
  final bool write;

  @override
  Widget build(BuildContext context) =>
      BlocBuilder<MeContextCubit, MeContextState>(
        builder: (context, state) {
          if (allowsSchoolModule(state.context, module, write: write)) {
            return builder(context);
          }
          final readOnly = state.context?.isModuleReadOnly(module) ?? false;
          return Scaffold(
            appBar: AppBar(),
            body: Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text(
                  readOnly
                      ? 'This feature is read-only at your school.'
                      : 'This feature is unavailable at your school.',
                ),
              ),
            ),
          );
        },
      );
}

/// Preserve the existing page builder and adaptive layout when guarding a route.
Route<dynamic> guardModuleRoute(
  Route<dynamic> route,
  String module, {
  bool write = false,
}) {
  if (route is! CupertinoPageRoute<dynamic>) return route;
  return AdaptivePageRoute<dynamic>(
    settings: route.settings,
    maintainState: route.maintainState,
    fullscreenDialog: route.fullscreenDialog,
    contentColumn: route is AdaptivePageRoute<dynamic>
        ? route.contentColumn
        : true,
    builder: (context) =>
        ModuleAccessPage(module: module, write: write, builder: route.builder),
  );
}
