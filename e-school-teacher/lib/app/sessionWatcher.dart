import 'package:eschool_academics/eschool_academics.dart';

import 'dart:async';

import 'package:eschool_teacher/app/deepLinks.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/cubits/authCubit.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Keeps what the server says about the signed-in user (`/me/context`) and
/// the school switcher in step with sign-in and sign-out, and opens screens
/// that links ask for. Sits under the app-wide providers, above the
/// MaterialApp.
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
            } else if (state is Unauthenticated) {
              EndpointAvailability.forget();
              context.read<MeContextCubit>().clear();
              context.read<SchoolSelectionCubit>().clear();
            }
          },
        ),
        // Another school may have other screens deployed: ask again.
        BlocListener<SchoolSelectionCubit, SchoolSelectionState>(
          listenWhen: (previous, current) =>
              previous.selectedId != current.selectedId,
          listener: (context, _) => EndpointAvailability.forget(),
        ),
        // School-wide pushes come on each school's own topics: the device
        // joins those of the schools /me/context names.
        BlocListener<MeContextCubit, MeContextState>(
          listenWhen: (previous, current) =>
              previous.context != current.context,
          listener: (context, state) {
            final List<SchoolMembership> schools =
                state.context?.schools ?? const [];
            if (context.read<AuthCubit>().state is! Authenticated ||
                schools.isEmpty) {
              return;
            }
            NotificationUtility.syncSchoolTopics(
              audience: NotificationAudience.teachers,
              schoolIds: schools.map((membership) => membership.school.id),
            );
          },
        ),
        // The school switcher lists the schools /me/context names.
        BlocListener<MeContextCubit, MeContextState>(
          listenWhen: (previous, current) =>
              previous.context != current.context,
          listener: (context, state) => context
              .read<SchoolSelectionCubit>()
              .syncWithContext(state.context),
        ),
      ],
      // Back in front after a while, or after a context.changed push in
      // the background: fetch /me/context again.
      child: MeContextRefresher(
        isSignedIn: (context) =>
            context.read<AuthCubit>().state is Authenticated,
        takeChangeNotice: () =>
            const NotificationPreferences().takeContextChanged(),
        child: ContentCacheSessionGuard(child: widget.child),
      ),
    );
  }
}

/// After a school switch: the new school's app settings first (its year
/// and other settings), then back to home, where everything else is fetched
/// again for the new school.
Future<void> returnHomeAfterSchoolSwitch() async {
  final NavigatorState? navigator = UiUtils.rootNavigatorKey.currentState;
  if (navigator == null) {
    return;
  }
  await navigator.context.read<AppConfigurationCubit>().fetchAppConfiguration();
  if (!navigator.mounted) {
    return;
  }
  navigator.pushNamedAndRemoveUntil(Routes.home, (_) => false);
}
