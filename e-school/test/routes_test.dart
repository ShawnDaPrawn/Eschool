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
    Routes.childAssignments: ChildSubjectsArguments(
      childId: 4,
      subjects: subjects,
    ),
    Routes.exam: ChildSubjectsArguments(childId: 4, subjects: subjects),
    Routes.childResults: ChildSubjectsArguments(childId: 4, subjects: subjects),
    Routes.subjectWiseReport: ChildSubjectsArguments(
      childId: 4,
      subjects: subjects,
    ),
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
    Routes.childFees: student,
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
