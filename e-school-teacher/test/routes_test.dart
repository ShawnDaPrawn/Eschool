import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/deepLinks.dart';
import 'package:eschool_teacher/app/routeArguments.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/models/lesson.dart';
import 'package:eschool_teacher/data/models/student.dart';
import 'package:eschool_teacher/data/models/studentResult.dart';
import 'package:eschool_teacher/data/models/subject.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';

Route<dynamic> open(String name, [Object? arguments]) =>
    Routes.onGenerateRouted(RouteSettings(name: name, arguments: arguments));

void main() {
  final classSection = ClassSectionDetails.fromJson({
    'id': 3,
    'section': {'id': 1, 'name': 'A'},
    'class': {'id': 5, 'name': 'Grade 5'},
  });
  final subject = Subject.fromJson({'id': 1, 'name': 'Maths'});
  final lesson = Lesson.fromJson({'id': 2, 'name': 'Fractions'});
  final student = Student.fromJson({'id': 7});
  final material = StudyMaterial.fromURL('https://school.test/a.pdf');

  // Each route reads its typed arguments when it is built; a wrong type
  // fails here instead of on a teacher's phone.
  final Map<String, Object?> typedArguments = {
    Routes.classScreen: ClassScreenArguments(
      classSection: classSection,
      isClassTeacher: true,
    ),
    Routes.subject: SubjectScreenArguments(
      subject: subject,
      classSectionDetails: classSection,
    ),
    Routes.topicsByLesson: TopicsByLessonArguments(
      lesson: lesson,
      subject: subject,
      classSectionDetails: classSection,
    ),
    Routes.addOrEditLesson: AddOrEditLessonArguments(subject: subject),
    Routes.addOrEditTopic: AddOrEditTopicArguments(lesson: lesson),
    Routes.addOrEditAnnouncement: AddOrEditAnnouncementArguments(
      subject: subject,
    ),
    Routes.addAssignment: const AddOrEditAssignmentArguments(),
    Routes.searchStudent: SearchStudentArguments(
      students: [student],
      fromAttendanceScreen: false,
    ),
    Routes.attendance: [student],
    Routes.examTimeTable: const ExamTimeTableArguments(
      examId: 2,
      examName: 'June exams',
      classId: 5,
      className: 'Grade 5',
    ),
    Routes.resultList: const ResultListArguments(classSectionId: 3),
    Routes.addResult: AddResultArguments(
      studentResultData: StudentResult.fromJson({}),
      studentName: 'Asha',
      studentId: 7,
      classSectionId: 3,
    ),
    Routes.pdfFileView: PdfFileViewArguments(studyMaterial: material),
    Routes.imageFileView: ImageFileViewArguments(studyMaterial: material),
  };

  typedArguments.forEach((name, arguments) {
    test('$name takes its typed arguments', () {
      expect(open(name, arguments), isA<PageRoute<dynamic>>());
    });
  });

  test('the add and edit screens open without arguments', () {
    expect(open(Routes.addOrEditLesson), isA<PageRoute<dynamic>>());
    expect(open(Routes.addOrEditTopic), isA<PageRoute<dynamic>>());
    expect(open(Routes.addOrEditAnnouncement), isA<PageRoute<dynamic>>());
  });

  test('the old untyped map is refused', () {
    expect(
      () => open(Routes.classScreen, {'isClassTeacher': true}),
      throwsA(isA<TypeError>()),
    );
  });

  test('the shared screens have addresses and open as routes', () {
    final parser = DeepLinkParser(routes: deepLinkRoutes);
    expect(
      parser.parse(Uri.parse('https://teach.navigateed.co.za/my-school')),
      const DeepLinkTarget(Routes.mySchool),
    );
    expect(
      parser.parsePath('/notifications/settings'),
      const DeepLinkTarget(Routes.notificationSettings),
    );
    expect(
      parser.parsePath('/profile-picture'),
      const DeepLinkTarget(Routes.profilePicture),
    );
    // A guardian's screens are not the teacher app's.
    expect(parser.parsePath('/approvals'), isNull);
    expect(parser.parsePath('/invite/t0k3n'), isNull);
    for (final String name in [
      Routes.notificationSettings,
      Routes.mySchool,
      Routes.profilePicture,
    ]) {
      expect(
        Routes.onGenerateRouted(RouteSettings(name: name)),
        isA<PageRoute<dynamic>>(),
      );
    }
  });

  test('notification actions resolve to guarded feature routes', () {
    final parser = DeepLinkParser(routes: deepLinkRoutes);
    expect(parser.parsePath('/handles'), const DeepLinkTarget(Routes.handles));
    expect(
      parser.parsePath('/chat'),
      const DeepLinkTarget(Routes.chatUserPage),
    );
    expect(open(Routes.chatUserPage), isA<PageRoute<dynamic>>());
  });

  test('every link opens a route the app has', () {
    final parser = DeepLinkParser(routes: deepLinkRoutes);
    for (final route in deepLinkRoutes) {
      final target = parser.parse(
        Uri.parse('https://teach.navigateed.co.za${route.path}'),
      )!;
      expect(
        open(target.routeName, target.arguments),
        isA<PageRoute<dynamic>>(),
        reason: route.path,
      );
    }
  });
}
