import 'package:eschool/app/app.dart';
import 'package:eschool/ui/screens/exam/onlineExam/models/answerOption.dart';
import 'package:eschool/ui/screens/exam/onlineExam/models/question.dart';
import 'package:eschool/ui/screens/exam/onlineExam/widgets/optionContainer.dart';
import 'package:eschool/ui/screens/exam/onlineExam/widgets/questionContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/math_tap.dart'
    if (dart.library.js_interop) 'support/math_tap_web.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('exam maths renders independently and an option submits its id', (
    tester,
  ) async {
    NotificationUtility.enabled = false;
    await prepareApp(useFirebase: false);
    var questionRendered = false;
    var optionRendered = false;
    List<int> submitted = [];

    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light(),
        home: Scaffold(
          body: Column(
            children: [
              QuestionContainer(
                isMathQuestion: true,
                question: Question(question: r'\(x^2 = 4\)', marks: ''),
                note: '',
                onMathRendered: (height) => questionRendered = height > 5,
              ),
              OptionContainer(
                quizType: 1,
                constraints: const BoxConstraints(
                  maxWidth: 350,
                  maxHeight: 700,
                ),
                answerOption: AnswerOption(id: 17, option: r'\(x = 2\)'),
                submittedAnswerId: '',
                totalAnswers: 1,
                submitAnswer: (List<int> ids) => submitted = ids,
                onMathRendered: (height) => optionRendered = height > 5,
              ),
            ],
          ),
        ),
      ),
    );

    final deadline = DateTime.now().add(const Duration(seconds: 45));
    while ((!questionRendered || !optionRendered) &&
        DateTime.now().isBefore(deadline)) {
      await tester.pump(const Duration(milliseconds: 200));
    }
    expect(
      questionRendered,
      isTrue,
      reason: 'MathJax must finish rendering the question',
    );
    expect(
      optionRendered,
      isTrue,
      reason: 'The option must have its own renderer',
    );
    await tapMathOption(tester, find.byType(OptionContainer), '17');
    await tester.pump();
    expect(submitted, [17]);
    expect(tester.takeException(), isNull);
  });
}
