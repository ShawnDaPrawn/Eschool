import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/app/routeArguments.dart';
import 'package:eschool/cubits/semestersCubit.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool/ui/screens/home/widgets/examContainer.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ExamScreen extends StatelessWidget {
  const ExamScreen({super.key, this.childId, this.subjects});
  final int? childId;
  final List<Subject>? subjects;

  static Route route(RouteSettings routeSettings) {
    final arguments = routeSettings.arguments! as ChildSubjectsArguments;
    return AdaptivePageRoute(
      builder: (_) => BlocProvider<SemestersCubit>(
        create: (context) => SemestersCubit(),
        child: ExamScreen(
          childId: arguments.childId,
          subjects: arguments.subjects,
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
