import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'me_context_cubit.dart';

/// Every context refresh ends the old credential/session reader, including a
/// loading/loaded pair coalesced into one rendered frame.
class MeContextLiveBoundary extends StatefulWidget {
  const MeContextLiveBoundary({
    required this.client,
    required this.child,
    this.origin = 'https://app.navigateed.co.za',
    super.key,
  });
  final ApiClient client;
  final Widget child;
  final String origin;
  @override
  State<MeContextLiveBoundary> createState() => _MeContextLiveBoundaryState();
}

class _MeContextLiveBoundaryState extends State<MeContextLiveBoundary> {
  int _generation = 0;
  @override
  Widget build(BuildContext context) =>
      BlocConsumer<MeContextCubit, MeContextState>(
        listener: (_, state) {
          if (state is MeContextLoading) _generation++;
        },
        builder: (context, state) {
          final pinned = state is MeContextLoaded ? state.context : null;
          final cubit = context.read<MeContextCubit>();
          final generation = _generation;
          return LiveUpdatesHost(
            client: widget.client,
            schoolId: pinned?.currentSchoolId ?? 0,
            accountId: pinned?.user?.id ?? 0,
            enabled: pinned != null && pinned.isModuleOn('live_updates'),
            authorizationKey: (generation, pinned),
            contextActive: () =>
                mounted &&
                generation == _generation &&
                cubit.state is MeContextLoaded &&
                identical(cubit.state.context, pinned),
            origin: widget.origin,
            child: widget.child,
          );
        },
      );
}
