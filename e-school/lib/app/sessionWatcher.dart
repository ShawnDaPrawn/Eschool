import 'dart:async';

import 'package:eschool/app/deepLinks.dart';
import 'package:eschool/app/selectedChild.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Keeps what the server says about the signed-in user (`/me/context`), a
/// parent's children in the child switcher and the school switcher in step
/// with sign-in and sign-out, and opens screens that links ask for. Sits
/// under the app-wide providers, above the MaterialApp.
class SessionWatcher extends StatefulWidget {
  const SessionWatcher({required this.child, super.key});

  final Widget child;

  @override
  State<SessionWatcher> createState() => _SessionWatcherState();
}

class _SessionWatcherState extends State<SessionWatcher> {
  StreamSubscription<DeepLinkTarget>? _links;

  @override
  void initState() {
    super.initState();
    if (context.read<AuthCubit>().state is Authenticated) {
      context.read<MeContextCubit>().load();
      _syncChildren();
    }
    // A context.changed push while the app is in front: fetch it now.
    NotificationUtility.onContextChanged = () {
      if (mounted && context.read<AuthCubit>().state is Authenticated) {
        context.read<MeContextCubit>().load();
      }
    };
    _links = deepLinks.targets.listen(
      (target) => openDeepLink(
        target,
        signedIn: context.read<AuthCubit>().state is Authenticated,
      ),
    );
    deepLinks.start();
  }

  @override
  void dispose() {
    _links?.cancel();
    NotificationUtility.onContextChanged = null;
    super.dispose();
  }

  /// Lists a parent's children in the child switcher: from `/me/context`
  /// when the server sends them, else from the v1 sign-in. The child picked
  /// last time stays picked.
  void _syncChildren() {
    if (!context.read<AuthCubit>().isParent()) {
      return;
    }
    context.read<ChildSelectionCubit>().setChildren(switcherChildren(context));
  }

  @override
  Widget build(BuildContext context) {
    return MultiBlocListener(
      listeners: [
        BlocListener<AuthCubit, AuthState>(
          listenWhen: (previous, current) =>
              previous.runtimeType != current.runtimeType,
          listener: (context, state) {
            if (state is Authenticated) {
              context.read<MeContextCubit>().load();
              _syncChildren();
            } else if (state is Unauthenticated) {
              context.read<MeContextCubit>().clear();
              context.read<ChildSelectionCubit>().clear();
              context.read<SchoolSelectionCubit>().clear();
            }
          },
        ),
        // A refreshed profile can list other children.
        BlocListener<AuthCubit, AuthState>(
          listenWhen: (previous, current) =>
              previous is Authenticated &&
              current is Authenticated &&
              previous.parent != current.parent,
          listener: (context, _) => _syncChildren(),
        ),
        BlocListener<MeContextCubit, MeContextState>(
          listenWhen: (previous, current) =>
              previous.context?.children != current.context?.children,
          listener: (context, _) => _syncChildren(),
        ),
        // The school switcher lists the schools /me/context names.
        BlocListener<MeContextCubit, MeContextState>(
          listenWhen: (previous, current) => previous.context != current.context,
          listener: (context, state) =>
              context.read<SchoolSelectionCubit>().syncWithContext(state.context),
        ),
        // A parent's child screens are sent for the child's school: picking
        // a child at another school moves there, with that school's context.
        BlocListener<ChildSelectionCubit, ChildSelectionState>(
          listenWhen: (previous, current) =>
              previous.selected?.schoolId != current.selected?.schoolId,
          listener: (context, state) async {
            final int schoolId = state.selected?.schoolId ?? 0;
            final MeContextCubit meContext = context.read<MeContextCubit>();
            if (await context.read<SchoolSelectionCubit>().follow(schoolId)) {
              meContext.load();
            }
          },
        ),
        // A parent who switches school sees a child at that school.
        BlocListener<SchoolSelectionCubit, SchoolSelectionState>(
          listenWhen: (previous, current) =>
              previous.selectedId != current.selectedId,
          listener: (context, state) {
            final ChildSelectionCubit children = context
                .read<ChildSelectionCubit>();
            final int? schoolId = state.selectedId;
            final MeChild? shown = children.selected;
            if (schoolId == null ||
                shown == null ||
                shown.schoolId == 0 ||
                shown.schoolId == schoolId) {
              return;
            }
            for (final MeChild child in children.state.children) {
              if (child.schoolId == schoolId) {
                children.select(child.id);
                return;
              }
            }
          },
        ),
      ],
      // Back in front after a while, or after a context.changed push in
      // the background: fetch /me/context again.
      child: MeContextRefresher(
        isSignedIn: (context) =>
            context.read<AuthCubit>().state is Authenticated,
        takeChangeNotice: () =>
            const NotificationPreferences().takeContextChanged(),
        child: widget.child,
      ),
    );
  }
}

/// After a school switch: back to home, where everything is fetched again
/// for the new school.
void returnHomeAfterSchoolSwitch() {
  final NavigatorState? navigator = UiUtils.rootNavigatorKey.currentState;
  final bool isParent = navigator?.context.read<AuthCubit>().isParent() ?? false;
  navigator?.pushNamedAndRemoveUntil(
    isParent ? Routes.parentHome : Routes.home,
    (_) => false,
  );
}
