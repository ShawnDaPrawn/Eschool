import 'package:eschool/app/selectedChild.dart';
import 'package:eschool/cubits/attendanceCubit.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/ui/widgets/attendanceContainer.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ChildAttendanceScreen extends StatelessWidget {
  const ChildAttendanceScreen({required this.childId, super.key});
  final int childId;

  /// The attendance of the child picked in the child switcher.
  static Route route(RouteSettings routeSettings) {
    return childRoute(
      (context, child) => withChild(
        child,
        (child) => BlocProvider<AttendanceCubit>(
          create: (context) => AttendanceCubit(StudentRepository()),
          child: ChildAttendanceScreen(childId: child.id),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: AttendanceContainer(
        childId: childId,
      ),
    );
  }
}
