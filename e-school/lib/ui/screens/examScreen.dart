import 'package:eschool/app/selectedChild.dart';
import 'package:eschool/cubits/semestersCubit.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool/ui/screens/home/widgets/examContainer.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ExamScreen extends StatelessWidget {
  const ExamScreen({super.key, this.childId, this.subjects});
  final int? childId;
  final List<Subject>? subjects;

  /// A parent sees the exams of the child picked in the child switcher; a
  /// learner sees their own.
  static Route route(RouteSettings routeSettings) {
    return childRoute(
      (context, child) => BlocProvider<SemestersCubit>(
        create: (context) => SemestersCubit(),
        child: child == null
            ? const ExamScreen()
            : ChildSubjectsBuilder(
                childId: child.id,
                builder: (subjects) =>
                    ExamScreen(childId: child.id, subjects: subjects),
              ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: ExamContainer(childId: childId, subjects: subjects),
    );
  }
}
