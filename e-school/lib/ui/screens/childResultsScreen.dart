import 'package:eschool/app/routeArguments.dart';
import 'package:eschool/cubits/resultsCubit.dart';
import 'package:eschool/cubits/semestersCubit.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/ui/widgets/resultsContainer.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ChildResultsScreen extends StatelessWidget {
  const ChildResultsScreen({
    required this.childId,
    required this.subjects,
    super.key,
  });
  final int childId;
  final List<Subject>? subjects;

  static Route route(RouteSettings routeSettings) {
    final arguments = routeSettings.arguments! as ChildSubjectsArguments;
    return CupertinoPageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider<ResultsCubit>(
            create: (context) => ResultsCubit(StudentRepository()),
          ),
          BlocProvider<SemestersCubit>(create: (context) => SemestersCubit()),
        ],
        child: ChildResultsScreen(
          childId: arguments.childId,
          subjects: arguments.subjects,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: ResultsContainer(childId: childId, subjects: subjects),
    );
  }
}
