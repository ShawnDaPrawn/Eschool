import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/studentSubjectsCubit.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

// A parent's child screens show the child picked in the child switcher
// (ChildSelectionCubit, eschool_session) instead of a child id handed over on
// every tap. The v1 endpoints behind them still take the child id: the
// screens pass the selected child's.

/// The children the switcher lists: `/me/context` `children` when the
/// server sends them, otherwise the children of the v1 parent sign-in, so
/// the app works with a server from before API v2 too.
List<MeChild> switcherChildren(BuildContext context) {
  final List<MeChild>? fromContext = context
      .read<MeContextCubit>()
      .context
      ?.children;
  if (fromContext != null && fromContext.isNotEmpty) {
    return fromContext;
  }
  final String schoolName = context
      .read<AppConfigurationCubit>()
      .getAppConfiguration()
      .schoolName;
  return [
    for (final Student student
        in context.read<AuthCubit>().getParentDetails().children)
      childFromStudent(student, schoolName: schoolName),
  ];
}

/// A v1 child as the switcher shows it.
MeChild childFromStudent(Student student, {String schoolName = ''}) => MeChild(
  id: student.id,
  userId: student.userId,
  name: student.getFullName(),
  schoolName: schoolName,
  avatarUrl: student.image,
  homeroom: student.classSectionName,
);

/// The v1 [Student] of the child on show: from the parent's sign-in data
/// when it has them, otherwise built from what `/me/context` says (a child
/// the v1 answer does not list).
Student? selectedStudent(BuildContext context, {ChildSelectionState? state}) {
  final List<Student> students = context
      .read<AuthCubit>()
      .getParentDetails()
      .children;
  final MeChild? child =
      (state ?? context.read<ChildSelectionCubit>().state).selected;
  if (child == null) {
    return students.isEmpty ? null : students.first;
  }
  for (final Student student in students) {
    if (student.id == child.id) {
      return student;
    }
  }
  final List<String> names = child.name.split(' ');
  return Student.fromJson({
    'id': child.id,
    'user_id': child.userId,
    'first_name': names.first,
    'last_name': names.skip(1).join(' '),
    'image': child.avatarUrl,
    'class_section_name': child.homeroom,
    'admission_no': child.studentNumber,
  });
}

/// Builds a child screen for the child on show, and builds it afresh (new
/// state, new cubits, a new fetch) when another child is picked. [builder]
/// gets `null` for a learner, whose screens show themselves.
class SelectedChildScope extends StatelessWidget {
  const SelectedChildScope({required this.builder, super.key});

  final Widget Function(BuildContext context, Student? child) builder;

  @override
  Widget build(BuildContext context) {
    if (!context.read<AuthCubit>().isParent()) {
      return builder(context, null);
    }
    return BlocBuilder<ChildSelectionCubit, ChildSelectionState>(
      buildWhen: (previous, current) =>
          previous.selectedId != current.selectedId,
      builder: (context, state) {
        final Student? student = selectedStudent(context, state: state);
        return KeyedSubtree(
          key: ValueKey<int?>(student?.id),
          child: builder(context, student),
        );
      },
    );
  }
}

/// The route of a screen about the child on show: the screen in a column,
/// with the child switcher above it (beside it on a desktop) when the
/// parent has more than one child.
Route<dynamic> childRoute(
  Widget Function(BuildContext context, Student? child) builder,
) {
  return AdaptivePageRoute(
    contentColumn: false,
    builder: (_) => ChildSwitcherFrame(
      child: ContentColumn(child: SelectedChildScope(builder: builder)),
    ),
  );
}

/// What a parent-only child screen shows when no child is known (a parent
/// with no children linked yet).
Widget withChild(Student? child, Widget Function(Student child) builder) =>
    child == null ? const Scaffold() : builder(child);

/// Gives a child screen the child's subjects (for its filters) from the
/// app's one [ChildSubjectsCubit], fetching them when they are not there
/// yet, and rebuilds it when they arrive.
class ChildSubjectsBuilder extends StatefulWidget {
  const ChildSubjectsBuilder({
    required this.childId,
    required this.builder,
    super.key,
  });

  final int childId;
  final Widget Function(List<Subject> subjects) builder;

  @override
  State<ChildSubjectsBuilder> createState() => _ChildSubjectsBuilderState();
}

class _ChildSubjectsBuilderState extends State<ChildSubjectsBuilder> {
  @override
  void initState() {
    super.initState();
    context.read<ChildSubjectsCubit>().ensureFor(widget.childId);
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<ChildSubjectsCubit, ChildSubjectsState>(
      builder: (context, _) => widget.builder(
        context.read<ChildSubjectsCubit>().getSubjectsForAssignmentContainer(),
      ),
    );
  }
}
