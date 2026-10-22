import 'package:eschool_session/src/context/me_context_cubit.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Fetches `/me/context` again when the app comes back to the front and the
/// one kept is older than [MeContextCubit.staleAfter], or a
/// `context.changed` push arrived while the app was in the background
/// ([takeChangeNotice]). Sits under the app-wide providers.
class MeContextRefresher extends StatefulWidget {
  const MeContextRefresher({
    required this.isSignedIn,
    required this.child,
    this.takeChangeNotice,
    super.key,
  });

  /// Whether someone is signed in: nobody's context is fetched otherwise.
  final bool Function(BuildContext context) isSignedIn;

  /// Answers, once, whether a `context.changed` push arrived since the last
  /// call (the app's background handler notes it).
  final Future<bool> Function()? takeChangeNotice;

  final Widget child;

  @override
  State<MeContextRefresher> createState() => _MeContextRefresherState();
}

class _MeContextRefresherState extends State<MeContextRefresher>
    with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  Future<void> didChangeAppLifecycleState(AppLifecycleState state) async {
    if (state != AppLifecycleState.resumed || !widget.isSignedIn(context)) {
      return;
    }
    final MeContextCubit cubit = context.read<MeContextCubit>();
    final bool changed = await widget.takeChangeNotice?.call() ?? false;
    if (changed) {
      await cubit.load();
    } else {
      await cubit.refreshIfStale();
    }
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
