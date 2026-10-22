import 'package:eschool/app/deepLinks.dart';
import 'package:eschool/app/routeArguments.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';

Route<dynamic> open(String name, [Object? arguments]) =>
    Routes.onGenerateRouted(RouteSettings(name: name, arguments: arguments));

void main() {
  final student = Student.fromJson({'id': 4});
  final subjects = [
    Subject.fromJson({'id': 1, 'name': 'Maths'}),
  ];
  final material = StudyMaterial.fromURL('https://school.test/a.pdf');

  // Each route reads its typed arguments when it is built; a wrong type
  // fails here instead of on a learner's phone.
  final Map<String, Object?> typedArguments = {
    Routes.examTimeTable: const ExamTimeTableArguments(
      examId: 2,
      examName: 'June exams',
    ),
    Routes.feesDetails: FeesDetailsArguments(
      studentDetails: student,
      sessionYearId: 1,
    ),
    Routes.paymentVerify: PaymentVerificationArguments(
      studentDetails: student,
      transactionId: 9,
      isStudentPaying: false,
      status: 1,
    ),
    Routes.webViewPaymentScreen: 'https://checkout.test/pay',
    Routes.manageLeaves: student,
    Routes.addLeave: student,
    Routes.academicCalendar: const AcademicCalendarArguments(hasBack: true),
    Routes.playVideo: PlayVideoArguments(
      currentlyPlayingVideo: material,
      relatedVideos: [material],
    ),
    Routes.pdfFileView: PdfFileViewArguments(studyMaterial: material),
    Routes.imageFileView: ImageFileViewArguments(studyMaterial: material),
    Routes.resultOnline: const ResultOnlineArguments(
      examId: 1,
      examName: 'Quiz',
      subjectName: 'Maths',
    ),
    Routes.subjectDetails: SubjectDetailsArguments(subject: subjects.first),
  };

  typedArguments.forEach((name, arguments) {
    test('$name takes its typed arguments', () {
      expect(open(name, arguments), isA<PageRoute<dynamic>>());
    });
  });

  // A parent's child screens show the child picked in the child switcher:
  // they are opened with no child at all.
  for (final String name in [
    Routes.studentDetails,
    Routes.childAssignments,
    Routes.childTeachers,
    Routes.childAttendance,
    Routes.childTimeTable,
    Routes.childResults,
    Routes.childFees,
    Routes.exam,
    Routes.subjectWiseReport,
    Routes.manageLeaves,
  ]) {
    test('$name opens for the child on show, without a child id', () {
      expect(open(name), isA<PageRoute<dynamic>>());
    });
  }

  test('a fee reminder opens the fees of the child it names', () {
    expect(
      open(Routes.feesDetails, const FeesDetailsArguments.forChildId(4)),
      isA<PageRoute<dynamic>>(),
    );
  });

  test('the old untyped map is refused', () {
    expect(
      () => open(Routes.examTimeTable, {'examID': 2, 'examName': 'June'}),
      throwsA(isA<TypeError>()),
    );
  });

  test('the file viewers and calendar open without arguments', () {
    expect(open(Routes.pdfFileView), isA<PageRoute<dynamic>>());
    expect(open(Routes.imageFileView), isA<PageRoute<dynamic>>());
    expect(open(Routes.academicCalendar), isA<PageRoute<dynamic>>());
  });

  group('deep links', () {
    final parser = DeepLinkParser(routes: deepLinkRoutes);

    test('portal addresses map to the app\'s routes', () {
      expect(
        parser.parse(Uri.parse('https://app.navigateed.co.za/notices')),
        const DeepLinkTarget(Routes.noticeBoard),
      );
      expect(
        parser
            .parse(Uri.parse('https://app.navigateed.co.za/calendar'))!
            .arguments,
        isA<AcademicCalendarArguments>(),
      );
    });

    test('the shared screens have addresses, and push links name them', () {
      expect(
        parser.parse(Uri.parse('https://app.navigateed.co.za/my-school')),
        const DeepLinkTarget(Routes.mySchool),
      );
      expect(
        parser.parsePath('/notifications/settings'),
        const DeepLinkTarget(Routes.notificationSettings),
      );
      expect(
        parser.parsePath('/approvals'),
        const DeepLinkTarget(Routes.approvals),
      );
      expect(
        parser.parse(Uri.parse('https://app.navigateed.co.za/invite/t0k3n')),
        const DeepLinkTarget(Routes.invitation, arguments: 't0k3n'),
      );
      expect(
        parser.parsePath('/profile-picture'),
        const DeepLinkTarget(Routes.profilePicture),
      );
      // The notifications list keeps its address.
      expect(
        parser.parsePath('/notifications'),
        const DeepLinkTarget(Routes.notifications),
      );
    });

    test('the shared screens open as routes', () {
      for (final String name in [
        Routes.notificationSettings,
        Routes.mySchool,
        Routes.approvals,
        Routes.profilePicture,
      ]) {
        expect(open(name), isA<PageRoute<dynamic>>(), reason: name);
      }
      expect(open(Routes.invitation, 't0k3n'), isA<PageRoute<dynamic>>());
    });

    test('every link opens a route the app has', () {
      for (final route in deepLinkRoutes) {
        final target = parser.parse(
          Uri.parse('https://app.navigateed.co.za${route.path}'),
        )!;
        expect(
          open(target.routeName, target.arguments),
          isA<PageRoute<dynamic>>(),
          reason: route.path,
        );
      }
    });
  });
}
