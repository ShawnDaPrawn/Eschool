import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Pinned repositories cannot leave learner data on screen after a context switch.
class AcademicContextBoundary extends StatefulWidget {
  const AcademicContextBoundary({
    required this.schoolId,
    required this.child,
    this.childId,
    super.key,
  });
  final int schoolId;
  final int? childId;
  final Widget child;
  @override
  State<AcademicContextBoundary> createState() =>
      _AcademicContextBoundaryState();
}

class _AcademicContextBoundaryState extends State<AcademicContextBoundary> {
  bool _valid = true;
  @override
  void initState() {
    super.initState();
    final school = context.read<SchoolSelectionCubit>().state.selectedId;
    final child = widget.childId == null
        ? null
        : context.read<ChildSelectionCubit>().state.selectedId;
    if ((school != null && school != widget.schoolId) ||
        (widget.childId != null && child != widget.childId)) {
      _valid = false;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted && (ModalRoute.of(context)?.isActive ?? false)) {
          Navigator.of(context).popUntil((route) => route.isFirst);
        }
      });
    }
  }

  void _clear() {
    if (!_valid) return;
    setState(() => _valid = false);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted && (ModalRoute.of(context)?.isActive ?? false)) {
        Navigator.of(context).popUntil((route) => route.isFirst);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    Widget child = _valid ? widget.child : const SizedBox.shrink();
    if (widget.childId != null) {
      child = BlocListener<ChildSelectionCubit, ChildSelectionState>(
        listenWhen: (old, next) => old.selectedId != next.selectedId,
        listener: (_, next) {
          if (next.selectedId != widget.childId) _clear();
        },
        child: child,
      );
    }
    return BlocListener<SchoolSelectionCubit, SchoolSelectionState>(
      listenWhen: (old, next) => old.selectedId != next.selectedId,
      listener: (_, next) {
        if (next.selectedId != widget.schoolId) _clear();
      },
      child: child,
    );
  }
}
