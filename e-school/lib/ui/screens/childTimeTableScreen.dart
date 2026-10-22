import 'package:eschool/app/selectedChild.dart';
import 'package:eschool/cubits/timeTableCubit.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/ui/widgets/timetableContainer.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ChildTimeTableScreen extends StatelessWidget {
  const ChildTimeTableScreen({required this.childId, super.key});
  final int childId;

  /// The timetable of the child picked in the child switcher.
  static Route route(RouteSettings routeSettings) {
    return childRoute(
      (context, child) => withChild(
        child,
        (child) => BlocProvider<TimeTableCubit>(
          create: (context) => TimeTableCubit(StudentRepository()),
          child: ChildTimeTableScreen(childId: child.id),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: TimeTableContainer(
        childId: childId,
      ),
    );
  }
}
