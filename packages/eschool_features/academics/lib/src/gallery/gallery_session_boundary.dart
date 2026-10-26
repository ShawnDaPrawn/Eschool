import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../localization/academic_text.dart';

bool galleryAccessStillCurrent(MeContext pinned, MeContext? current) =>
    current != null &&
    current.user?.id == pinned.user?.id &&
    current.currentSchoolId == pinned.currentSchoolId &&
    listEquals([...current.roles]..sort(), [...pinned.roles]..sort()) &&
    listEquals(current.children, pinned.children) &&
    current.approval == pinned.approval &&
    current.isModuleOn('gallery') &&
    allowsSchoolModule(current, 'gallery') &&
    current.isModuleReadOnly('gallery') == pinned.isModuleReadOnly('gallery');

class GallerySessionBoundary extends StatefulWidget {
  const GallerySessionBoundary({
    required this.pinned,
    required this.builder,
    super.key,
  });
  final MeContext pinned;
  final Widget Function(bool Function() active) builder;
  @override
  State<GallerySessionBoundary> createState() => _GallerySessionBoundaryState();
}

class _GallerySessionBoundaryState extends State<GallerySessionBoundary> {
  int _generation = 0;
  @override
  Widget build(BuildContext context) =>
      BlocConsumer<MeContextCubit, MeContextState>(
        listener: (_, state) {
          if (state is! MeContextLoaded ||
              !galleryAccessStillCurrent(widget.pinned, state.context))
            _generation++;
        },
        builder: (context, state) {
          final generation = _generation;
          bool active() =>
              mounted &&
              generation == _generation &&
              context.read<MeContextCubit>().state is MeContextLoaded &&
              galleryAccessStillCurrent(
                widget.pinned,
                context.read<MeContextCubit>().context,
              );
          return active()
              ? KeyedSubtree(
                  key: ValueKey(generation),
                  child: widget.builder(active),
                )
              : ScreenScaffold(
                  title: academicText(context, 'Private photos'),
                  body: Center(
                    child: state is MeContextLoading
                        ? const CircularProgressIndicator()
                        : const AcademicText(
                            'Your photo access changed. Reopen the gallery.',
                          ),
                  ),
                );
        },
      );
}
