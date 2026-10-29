import 'package:eschool/data/models/lesson.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool_core/eschool_core.dart';

// What the routes that take several values expect. A screen's route() reads
// its class and pushing the route builds one, so the compiler checks both
// ends. Routes that take one value pass it as it is: a Student (profile, a
// learner's own leaves), an Assignment, an Event, an ExamsOnline, a
// ChatUser, a GalleryAlbum, or the payment page URL. A parent's child
// screens (child details, assignments, teachers, attendance, timetable,
// calendar, exams, results, reports, fees, leaves) take no child: they show
// the child picked in the child switcher (selectedChild.dart). The file
// viewers' arguments are in eschool_core (ImageFileViewArguments,
// PdfFileViewArguments).

/// `Routes.subjectDetails`: a subject's lessons and notices.
class SubjectDetailsArguments {
  const SubjectDetailsArguments({required this.subject, this.childId});

  final Subject subject;

  /// The child a parent is looking at; null for the learner.
  final int? childId;
}

/// `Routes.chapterDetails`: a lesson's topics.
class ChapterDetailsArguments {
  const ChapterDetailsArguments({required this.lesson, this.childId});

  final Lesson lesson;
  final int? childId;
}

/// `Routes.topicDetails`: a topic's study material.
class TopicDetailsArguments {
  const TopicDetailsArguments({required this.topic, this.childId});

  final Topic topic;
  final int? childId;
}

/// `Routes.result`: one exam's result.
class ResultArguments {
  const ResultArguments({required this.result, this.childId});

  final Result result;
  final int? childId;
}

/// `Routes.resultOnline`: one online exam's result.
class ResultOnlineArguments {
  const ResultOnlineArguments({
    required this.examId,
    required this.examName,
    required this.subjectName,
    this.childId,
    this.totalMarks,
    this.obtainedMarks,
  });

  final int examId;
  final String examName;
  final String subjectName;
  final int? childId;
  final String? totalMarks;
  final String? obtainedMarks;
}

/// `Routes.examTimeTable`: an exam's timetable.
class ExamTimeTableArguments {
  const ExamTimeTableArguments({
    required this.examId,
    required this.examName,
    this.childId,
  });

  final int examId;
  final String examName;
  final int? childId;
}

/// `Routes.academicCalendar`. A parent sees the calendar of the child
/// picked in the child switcher.
class AcademicCalendarArguments {
  const AcademicCalendarArguments({this.hasBack = false});

  /// Whether the screen has a back button (it does when pushed, not when it
  /// is a tab of home).
  final bool hasBack;
}

/// `Routes.playVideo`: a video and the others of the same topic.
class PlayVideoArguments {
  const PlayVideoArguments({
    required this.currentlyPlayingVideo,
    required this.relatedVideos,
  });

  final StudyMaterial currentlyPlayingVideo;
  final List<StudyMaterial> relatedVideos;
}
