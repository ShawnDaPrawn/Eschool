import 'dart:async';

import 'package:eschool/app/deepLinks.dart';
import 'package:eschool/app/selectedChild.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Keeps what the server says about the signed-in user (`/me/context`) and
/// a parent's children in the child switcher in step with sign-in and
/// sign-out, and opens screens that links ask for. Sits under the app-wide
/// providers, above the MaterialApp.
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
      ],
      child: widget.child,
    );
  }
}
