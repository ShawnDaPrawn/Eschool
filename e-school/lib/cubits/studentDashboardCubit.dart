import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/data/models/assignment.dart';
import 'package:eschool/data/models/coreSubject.dart';
import 'package:eschool/data/models/electiveSubject.dart';
import 'package:eschool/data/models/event.dart';
import 'package:eschool/data/models/exam.dart';
import 'package:eschool/data/models/sliderDetails.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool/data/models/timeTableSlot.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class StudentDashboardState {}

class StudentDashboardInitial extends StudentDashboardState {}

class StudentDashboardFetchInProgress extends StudentDashboardState {}

class StudentDashboardFetchSuccess extends StudentDashboardState {
  StudentDashboardFetchSuccess({
    required this.todaysTimetable,
    required this.newAnnouncements,
    required this.coreSubjects,
    required this.doesClassHaveElectiveSubjects,
    required this.electiveSubjects,
    required this.sliders,
    required this.assignments,
    required this.upcomingExams,
    required this.events,
    required this.gallery,
  });
  final List<Subject> electiveSubjects;
  final List<Subject> coreSubjects;
  final List<SliderDetails> sliders;
  final List<Announcement> newAnnouncements;
  final List<TimeTableSlot> todaysTimetable;
  final List<Assignment> assignments;
  final List<Exam> upcomingExams;
  final List<Event> events;
  final Gallery? gallery;
  final bool doesClassHaveElectiveSubjects;

  StudentDashboardFetchSuccess copyWith({
    List<Subject>? electiveSubjects,
    List<Subject>? coreSubjects,
    List<SliderDetails>? sliders,
    List<Announcement>? newAnnouncements,
    List<TimeTableSlot>? todaysTimetable,
    List<Assignment>? assignments,
    bool? doesClassHaveElectiveSubjects,
    List<Exam>? exams,
    List<Event>? events,
    Gallery? gallery,
  }) {
    return StudentDashboardFetchSuccess(
      electiveSubjects: electiveSubjects ?? this.electiveSubjects,
      coreSubjects: coreSubjects ?? this.coreSubjects,
      sliders: sliders ?? this.sliders,
      newAnnouncements: newAnnouncements ?? this.newAnnouncements,
      todaysTimetable: todaysTimetable ?? this.todaysTimetable,
      assignments: assignments ?? this.assignments,
      doesClassHaveElectiveSubjects:
          doesClassHaveElectiveSubjects ?? this.doesClassHaveElectiveSubjects,
      upcomingExams: exams ?? upcomingExams,
      events: events ?? this.events,
      gallery: gallery ?? this.gallery,
    );
  }
}

class StudentDashboardFetchFailure extends StudentDashboardState {
  StudentDashboardFetchFailure(this.errorMessage);
  final String errorMessage;
}

class StudentDashboardCubit extends Cubit<StudentDashboardState> {
  StudentDashboardCubit({required this.studentRepository})
    : super(StudentDashboardInitial());
  final StudentRepository studentRepository;

  Future<void> fetchDashboard() async {
    emit(StudentDashboardFetchInProgress());
    try {
      final dashboardData = await studentRepository.fetchStudentDashboard();
      //If class have any elective subjects then of key of elective subject will be there
      //if elective subject key has empty list means student has not sleeted any
      //elective subjects

      //If there is not elective subjects key in result means no elective subjects
      //in given class
      emit(
        StudentDashboardFetchSuccess(
          doesClassHaveElectiveSubjects:
              dashboardData['subjects']['elective_subject'] != null,
          coreSubjects:
              ((dashboardData['subjects']['core_subject'] ?? []) as List)
                  .map((e) => CoreSubject.fromJson(json: Map.from(e ?? {})))
                  .toList(),
          electiveSubjects:
              ((dashboardData['subjects']['elective_subject'] ?? []) as List)
                  .map(
                    (e) => ElectiveSubject.fromJson(
                      electiveSubjectGroupId: 0,
                      json: Map.from(e['subject']),
                    ),
                  )
                  .toList(),
          sliders: ((dashboardData['sliders'] ?? []) as List)
              .map((sliderDetails) => SliderDetails.fromJson(sliderDetails))
              .toList(),
          newAnnouncements: ((dashboardData['announcements'] ?? []) as List)
              .map((e) => Announcement.fromJson(Map.from(e)))
              .toList(),
          todaysTimetable: ((dashboardData['timetables'] ?? []) as List)
              .map((e) => TimeTableSlot.fromJson(Map.from(e)))
              .toList(),
          assignments: ((dashboardData['assignments'] ?? []) as List)
              .map((e) => Assignment.fromJson(Map.from(e)))
              .toList(),
          upcomingExams: ((dashboardData['exams'] ?? []) as List)
              .map((e) => Exam.fromExamJson(Map.from(e)))
              .toList(),
          events: ((dashboardData['events'] ?? []) as List)
              .map((e) => Event.fromJson(Map.from(e)))
              .toList(),
          gallery: dashboardData['gallery'] == null
              ? null
              : Gallery.fromJson(dashboardData['gallery']),
        ),
      );
    } catch (e) {
      emit(StudentDashboardFetchFailure(e.toString()));
    }
  }

  void updateElectiveSubjects(List<ElectiveSubject> electiveSubjects) {
    if (state is StudentDashboardFetchSuccess) {
      emit(
        (state as StudentDashboardFetchSuccess).copyWith(
          electiveSubjects: electiveSubjects,
        ),
      );
    }
  }

  List<Subject> getSubjects() {
    if (state is StudentDashboardFetchSuccess) {
      final List<Subject> subjects = [];

      subjects.addAll(
        (state as StudentDashboardFetchSuccess).coreSubjects
            .where((element) => element.id != 0)
            .toList(),
      );

      subjects.addAll(
        (state as StudentDashboardFetchSuccess).electiveSubjects
            .where((element) => element.id != 0)
            .toList(),
      );

      return subjects;
    }

    return [];
  }

  List<Subject> getSubjectsForAssignmentContainer() {
    return getSubjects()..insert(
      0,
      Subject(
        bgColor: '',
        id: 0,
        code: '',
        image: '',
        mediumId: 1,
        name: '',
        type: '',
      ),
    );
  }

  List<Subject> getElectiveSubjects() {
    if (state is StudentDashboardFetchSuccess) {
      return (state as StudentDashboardFetchSuccess).electiveSubjects
          .where((element) => element.id != 0)
          .toList();
    }
    return [];
  }

  List<SliderDetails> getSliders() {
    if (state is StudentDashboardFetchSuccess) {
      return (state as StudentDashboardFetchSuccess).sliders;
    }
    return [];
  }

  void emitNewState({required StudentDashboardState newState}) {
    emit(newState);
  }

  List<Assignment> getAssignedAssignments() {
    if (state is StudentDashboardFetchSuccess) {
      return (state as StudentDashboardFetchSuccess).assignments
          .where((element) => element.assignmentSubmission.id == 0)
          .toList();
    }
    return [];
  }

  void updateAssignments(Assignment assignment) {
    if (state is StudentDashboardFetchSuccess) {
      final List<Assignment> updatedAssignments =
          (state as StudentDashboardFetchSuccess).assignments;
      final assignmentIndex = updatedAssignments.indexWhere(
        (element) => element.id == assignment.id,
      );
      if (assignmentIndex != -1) {
        updatedAssignments[assignmentIndex] = assignment;
        emit(
          (state as StudentDashboardFetchSuccess).copyWith(
            assignments: updatedAssignments,
          ),
        );
      }
    }
  }
}
