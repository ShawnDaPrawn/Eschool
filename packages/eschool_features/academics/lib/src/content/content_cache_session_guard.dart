import 'dart:async';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'content_store.dart';

/// Lives above all routes, so closed libraries cannot retain a prior scope key.
class ContentCacheSessionGuard extends StatelessWidget {
  const ContentCacheSessionGuard({
    required this.child,
    this.purge = SecureContentStore.purge,
    super.key,
  });
  final Widget child;
  final Future<void> Function() purge;
  Future<void> _purge() async {
    try {
      await purge();
    } catch (_) {
      /* Store closes and attempts key deletion before allowing another scope. */
    }
  }

  @override
  Widget build(BuildContext context) => MultiBlocListener(
    listeners: [
      BlocListener<SchoolSelectionCubit, SchoolSelectionState>(
        listenWhen: (old, next) =>
            old.selectedId != null && old.selectedId != next.selectedId,
        listener: (_, _) => unawaited(_purge()),
      ),
      BlocListener<MeContextCubit, MeContextState>(
        listenWhen: (old, next) =>
            old.context?.user?.id != null &&
            old.context?.user?.id != next.context?.user?.id,
        listener: (_, _) => unawaited(_purge()),
      ),
    ],
    child: child,
  );
}
