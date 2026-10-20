import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/models/event.dart';
import 'package:eschool_teacher/data/models/exam.dart';
import 'package:eschool_teacher/data/models/gallery.dart';
import 'package:eschool_teacher/data/models/staffLeave.dart';
import 'package:eschool_teacher/data/models/studentLeave.dart';
import 'package:eschool_teacher/data/models/timeTableSlot.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class DashboardState {}

class DashboardInitial extends DashboardState {}

class DashboardFetchInProgress extends DashboardState {}

class DashboardFetchSuccess extends DashboardState {
  DashboardFetchSuccess({
    required this.classes,
    required this.primaryClass,
    required this.pendingLeaveRequests,
    required this.todaysTimetable,
    required this.upcomingExams,
    required this.todayStaffLeave,
    required this.tomorrowStaffLeave,
    required this.upcomingStaffLeave,
    required this.upcomingEvents,
    required this.gallery,
  });
  final List<ClassSectionDetails> classes;

  //Primary class will be act as a class teacher's class
  final List<ClassSectionDetails>? primaryClass;

  final List<StudentLeave> pendingLeaveRequests;
  final List<TimeTableSlot> todaysTimetable;
  final List<Exam> upcomingExams;
  final List<StaffLeave> todayStaffLeave;
  final List<StaffLeave> tomorrowStaffLeave;
  final List<StaffLeave> upcomingStaffLeave;
  final List<Event> upcomingEvents;
  final Gallery? gallery;

  bool get hasStaffLeaves =>
      todayStaffLeave.isNotEmpty ||
      tomorrowStaffLeave.isNotEmpty ||
      upcomingStaffLeave.isNotEmpty;

  DashboardFetchSuccess copyWith({
    List<ClassSectionDetails>? classes,
    List<ClassSectionDetails>? primaryClass,
    List<StudentLeave>? pendingLeaveRequests,
    List<TimeTableSlot>? todaysTimetable,
    List<Exam>? upcomingExams,
    List<StaffLeave>? todayStaffLeave,
    List<StaffLeave>? tomorrowStaffLeave,
    List<StaffLeave>? upcomingStaffLeave,
    List<Event>? upcomingEvents,
    Gallery? gallery,
  }) {
    return DashboardFetchSuccess(
      classes: classes ?? this.classes,
      primaryClass: primaryClass ?? this.primaryClass,
      pendingLeaveRequests: pendingLeaveRequests ?? this.pendingLeaveRequests,
      todaysTimetable: todaysTimetable ?? this.todaysTimetable,
      upcomingExams: upcomingExams ?? this.upcomingExams,
      todayStaffLeave: todayStaffLeave ?? this.todayStaffLeave,
      tomorrowStaffLeave: tomorrowStaffLeave ?? this.tomorrowStaffLeave,
      upcomingStaffLeave: upcomingStaffLeave ?? this.upcomingStaffLeave,
      upcomingEvents: upcomingEvents ?? this.upcomingEvents,
      gallery: gallery ?? this.gallery,
    );
  }
}

class DashboardFetchFailure extends DashboardState {
  DashboardFetchFailure(this.errorMessage);
  final String errorMessage;
}

class DashboardCubit extends Cubit<DashboardState> {
  DashboardCubit(this._teacherRepository) : super(DashboardInitial());
  final TeacherRepository _teacherRepository;

  Future<void> fetchDashboard() async {
    emit(DashboardFetchInProgress());
    try {
      final result = await _teacherRepository.dashboard();

      emit(
        DashboardFetchSuccess(
          classes: result['classes'],
          primaryClass: result['primaryClass'],
          pendingLeaveRequests: result['pendingLeaveRequests'],
          todaysTimetable: result['todaysTimetable'],
          upcomingExams: result['upcomingExams'],
          todayStaffLeave: result['todayStaffLeave'],
          tomorrowStaffLeave: result['tomorrowStaffLeave'],
          upcomingStaffLeave: result['upcomingStaffLeave'],
          upcomingEvents: result['upcomingEvents'],
          gallery: result['gallery'],
        ),
      );
    } catch (e) {
      emit(DashboardFetchFailure(e.toString()));
    }
  }

  List<ClassSectionDetails>? primaryClass() {
    if (state is DashboardFetchSuccess) {
      return (state as DashboardFetchSuccess).primaryClass;
    }
    return [ClassSectionDetails.fromJson({})];
  }

  List<ClassSectionDetails> classes() {
    if (state is DashboardFetchSuccess) {
      return (state as DashboardFetchSuccess).classes;
    }
    return [];
  }

  List<ClassSectionDetails> getAllClasses() {
    final allClass = List<ClassSectionDetails>.from(classes());

    final primaryClassTemp = primaryClass();
    if (primaryClassTemp != null) {
      allClass.addAll(primaryClassTemp);
    }

    return allClass;
  }

  List<String> getClassSectionName() {
    return getAllClasses()
        .map((classSection) => classSection.getClassSectionNameWithMedium())
        .toList();
  }

  ClassSectionDetails getClassSectionDetails({required int index}) {
    return getAllClasses()[index];
  }

  ClassSectionDetails getClassSectionDetailsById(int classSectionId) {
    return getAllClasses()
        .where((element) => element.id == classSectionId)
        .first;
  }

  void removeStudentLeaveRequest(int studentLeaveId) {
    if (state is DashboardFetchSuccess) {
      final stateAs = state as DashboardFetchSuccess;
      if (stateAs.pendingLeaveRequests.any(
        (element) => element.id == studentLeaveId,
      )) {
        final List<StudentLeave> pendingLeaveRequests = [];
        for (int i = 0; i < stateAs.pendingLeaveRequests.length; i++) {
          if (stateAs.pendingLeaveRequests[i].id != studentLeaveId) {
            pendingLeaveRequests.add(stateAs.pendingLeaveRequests[i]);
          }
        }
        emit(stateAs.copyWith(pendingLeaveRequests: pendingLeaveRequests));
      }
    }
  }

  void updateTimetableItem(TimeTableSlot timetableSlot) {
    if (state is DashboardFetchSuccess) {
      final stateAs = state as DashboardFetchSuccess;
      if (!stateAs.todaysTimetable.any(
        (element) => element.id == timetableSlot.id,
      )) {
        return;
      }
      final List<TimeTableSlot> todaysTimetable = [];
      for (int i = 0; i < stateAs.todaysTimetable.length; i++) {
        if (stateAs.todaysTimetable[i].id != timetableSlot.id) {
          todaysTimetable.add(stateAs.todaysTimetable[i]);
        } else {
          todaysTimetable.add(timetableSlot);
        }
      }
      emit(stateAs.copyWith(todaysTimetable: todaysTimetable));
    }
  }
}
