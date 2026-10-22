import 'package:eschool/data/models/lesson.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool_core/eschool_core.dart';

// What the routes that take several values expect. A screen's route() reads
// its class and pushing the route builds one, so the compiler checks both
// ends. Routes that take one value pass it as it is: a Student (child
// details, profile, fees, leaves), an Assignment, an Event, an ExamsOnline,
// a ChatUser, a GalleryAlbum, a child id (teachers, attendance, timetable),
// or the payment page URL. The file viewers' arguments are in eschool_core
// (ImageFileViewArguments, PdfFileViewArguments).

/// A child's assignments, exams, results or reports, and the subjects to
/// filter them by.
class ChildSubjectsArguments {
  const ChildSubjectsArguments({required this.childId, required this.subjects});

  final int childId;
  final List<Subject> subjects;
}

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

/// `Routes.academicCalendar`.
class AcademicCalendarArguments {
  const AcademicCalendarArguments({this.hasBack = false, this.childId});

  /// Whether the screen has a back button (it does when pushed, not when it
  /// is a tab of home).
  final bool hasBack;
  final int? childId;
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

/// `Routes.feesDetails`: a learner's fees to pay. Either the learner or a
/// parent's child is given ([FeesDetailsArguments.new]), or only a child id,
/// from a fee reminder notification ([FeesDetailsArguments.forChildId]).
class FeesDetailsArguments {
  const FeesDetailsArguments({
    required Student this.studentDetails,
    required int this.sessionYearId,
    this.isStudentPaying = false,
  }) : childId = null;

  const FeesDetailsArguments.forChildId(this.childId)
    : studentDetails = null,
      sessionYearId = null,
      isStudentPaying = false;

  final Student? studentDetails;
  final int? sessionYearId;
  final bool isStudentPaying;
  final int? childId;
}

/// `Routes.paymentVerify`: what the payment returned.
class PaymentVerificationArguments {
  const PaymentVerificationArguments({
    required this.studentDetails,
    required this.transactionId,
    required this.isStudentPaying,
    this.status,
    this.paymentIntentId,
  });

  final Student studentDetails;
  final int transactionId;
  final bool isStudentPaying;

  /// 1 paid, 0 failed or cancelled; null for Stripe, whose payment intent is
  /// checked instead.
  final int? status;

  /// Stripe's PaymentIntent id.
  final String? paymentIntentId;
}
