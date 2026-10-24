import 'package:eschool/app/selectedChild.dart';
import 'package:eschool/cubits/resultsCubit.dart';
import 'package:eschool/cubits/termsCubit.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/ui/widgets/resultsContainer.dart';
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

  /// The results of the child picked in the child switcher.
  static Route route(RouteSettings routeSettings) {
    return childRoute(
      (context, child) => withChild(
        child,
        (child) => MultiBlocProvider(
          providers: [
            BlocProvider<ResultsCubit>(
              create: (context) => ResultsCubit(StudentRepository()),
            ),
            BlocProvider<TermsCubit>(create: (context) => TermsCubit()),
          ],
          child: ChildSubjectsBuilder(
            childId: child.id,
            builder: (subjects) =>
                ChildResultsScreen(childId: child.id, subjects: subjects),
          ),
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
