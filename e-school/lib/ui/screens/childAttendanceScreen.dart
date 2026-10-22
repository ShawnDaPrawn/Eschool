import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/cubits/attendanceCubit.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/ui/widgets/attendanceContainer.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ChildAttendanceScreen extends StatelessWidget {
  const ChildAttendanceScreen({required this.childId, super.key});
  final int childId;

  static Route route(RouteSettings routeSettings) {
    return AdaptivePageRoute(
      builder: (_) => BlocProvider<AttendanceCubit>(
        create: (context) => AttendanceCubit(StudentRepository()),
        child: ChildAttendanceScreen(
          childId: routeSettings.arguments! as int,
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
