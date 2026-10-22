import 'dart:async';

import 'package:eschool_teacher/app/deepLinks.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/authCubit.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
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
              context.read<MeContextCubit>().clear();
              context.read<SchoolSelectionCubit>().clear();
            }
          },
        ),
        // The school switcher lists the schools /me/context names.
        BlocListener<MeContextCubit, MeContextState>(
          listenWhen: (previous, current) => previous.context != current.context,
          listener: (context, state) =>
              context.read<SchoolSelectionCubit>().syncWithContext(state.context),
        ),
      ],
      child: widget.child,
    );
  }
}

/// After a school switch: back to home, where everything is fetched again
/// for the new school.
void returnHomeAfterSchoolSwitch() {
  UiUtils.rootNavigatorKey.currentState?.pushNamedAndRemoveUntil(
    Routes.home,
    (_) => false,
  );
}
