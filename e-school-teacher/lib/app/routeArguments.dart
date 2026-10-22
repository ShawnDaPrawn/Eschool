import 'package:eschool_teacher/data/models/assignment.dart';
import 'package:eschool_teacher/data/models/attendanceEnums.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/models/lesson.dart';
import 'package:eschool_teacher/data/models/student.dart';
import 'package:eschool_teacher/data/models/studentResult.dart';
import 'package:eschool_teacher/data/models/subject.dart';
import 'package:eschool_core/eschool_core.dart';

// What the routes that take several values expect. A screen's route() reads
// its class and pushing the route builds one, so the compiler checks both
// ends. Routes that take one value pass it as it is: a Student (student
// details), a list of students (attendance), an Assignment, an Event, a
// ChatUser or a GalleryAlbum. The file viewers' arguments are in
// eschool_core (ImageFileViewArguments, PdfFileViewArguments).

/// `Routes.classScreen`: one of the teacher's class sections.
class ClassScreenArguments {
  const ClassScreenArguments({
    required this.classSection,
    required this.isClassTeacher,
  });

  final ClassSectionDetails classSection;
  final bool isClassTeacher;
}

/// `Routes.subject`: a subject the teacher teaches in a class section.
class SubjectScreenArguments {
  const SubjectScreenArguments({
    required this.subject,
    required this.classSectionDetails,
  });

  final Subject subject;
  final ClassSectionDetails classSectionDetails;
}

/// `Routes.topicsByLesson`: a lesson's topics.
class TopicsByLessonArguments {
  const TopicsByLessonArguments({
    required this.lesson,
    required this.subject,
    required this.classSectionDetails,
  });

  final Lesson lesson;
  final Subject subject;
  final ClassSectionDetails classSectionDetails;
}

/// `Routes.addOrEditLesson`: a new lesson (no [lesson]) or one to edit, with
/// the subject and class to start from when known.
class AddOrEditLessonArguments {
  const AddOrEditLessonArguments({
    this.lesson,
    this.subject,
    this.classSectionDetails,
  });

  final Lesson? lesson;
  final Subject? subject;
  final ClassSectionDetails? classSectionDetails;
}

/// `Routes.addOrEditTopic`: a new topic (no [topic]) or one to edit.
class AddOrEditTopicArguments {
  const AddOrEditTopicArguments({
    this.topic,
    this.lesson,
    this.subject,
    this.classSectionDetails,
  });

  final Topic? topic;
  final Lesson? lesson;
  final Subject? subject;
  final ClassSectionDetails? classSectionDetails;
}

/// `Routes.addOrEditAnnouncement`: a new notice (no [announcement]) or one to
/// edit.
class AddOrEditAnnouncementArguments {
  const AddOrEditAnnouncementArguments({
    this.announcement,
    this.subject,
    this.classSectionDetails,
  });

  final Announcement? announcement;
  final Subject? subject;
  final ClassSectionDetails? classSectionDetails;
}

/// `Routes.addAssignment`: a new assignment, or [assignment] to edit.
class AddOrEditAssignmentArguments {
  const AddOrEditAssignmentArguments({
    this.editAssignment = false,
    this.assignment,
  });

  final bool editAssignment;
  final Assignment? assignment;
}

/// `Routes.searchStudent`: find a learner of the class, from the class
/// screen or while taking attendance.
class SearchStudentArguments {
  const SearchStudentArguments({
    required this.students,
    required this.fromAttendanceScreen,
    this.listOfAttendanceReport,
    this.leaveDetails = const {},
  });

  final List<Student> students;
  final bool fromAttendanceScreen;

  /// The register being taken, when [fromAttendanceScreen].
  final List<Map<int, AttendanceType?>>? listOfAttendanceReport;

  /// Learners on leave today, by student id.
  final Map<int, AttendanceLeaveType> leaveDetails;
}

/// `Routes.examTimeTable`: an exam's timetable for a class (or a learner).
class ExamTimeTableArguments {
  const ExamTimeTableArguments({
    required this.examId,
    required this.examName,
    required this.classId,
    required this.className,
    this.studentId,
  });

  final int examId;
  final String examName;
  final int classId;
  final String className;
  final int? studentId;
}

/// `Routes.resultList`: a learner's exam results.
class ResultListArguments {
  const ResultListArguments({
    required this.classSectionId,
    this.studentId,
    this.studentName,
    this.className,
  });

  final int classSectionId;
  final int? studentId;
  final String? studentName;
  final String? className;
}

/// `Routes.addResult`: enter a learner's marks for an exam.
class AddResultArguments {
  const AddResultArguments({
    required this.studentResultData,
    required this.studentName,
    required this.studentId,
    required this.classSectionId,
  });

  final StudentResult studentResultData;
  final String studentName;
  final int studentId;
  final int classSectionId;
}
