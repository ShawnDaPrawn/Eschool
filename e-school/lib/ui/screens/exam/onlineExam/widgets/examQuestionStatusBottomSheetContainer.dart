import 'package:eschool/ui/screens/exam/onlineExam/cubits/examOnlineCubit.dart';
import 'package:eschool/ui/screens/exam/onlineExam/models/question.dart';
import 'package:eschool/ui/widgets/customCircularProgressIndicator.dart';
import 'package:eschool/ui/widgets/customRoundedButton.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

// ignore: must_be_immutable
class ExamQuestionStatusBottomSheetContainer extends StatelessWidget {

  ExamQuestionStatusBottomSheetContainer({
    required this.pageController, required this.navigateToResultScreen, super.key,
    this.totalAttempted = 0,
  });
  final PageController pageController;
  final Function navigateToResultScreen;
  late int totalAttempted;

  Widget buildQuestionAttemptedByMarksContainer({
    required BuildContext context,
    required String questionMark,
    required List<Question> questions,
  }) {
    return Column(
      children: [
        const Divider(),
        Container(
          margin: const EdgeInsets.symmetric(horizontal: 10),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                '$questionMark ${UiUtils.getTranslatedLabel(context, marksKey)} ${UiUtils.getTranslatedLabel(context, questionsKey)}',
                style: TextStyle(
                  color: UiUtils.getColorScheme(context).onSurface,
                  fontSize: 14,
                ),
              ),
              Text(
                '[${questions.length}]',
                style: TextStyle(
                  color: UiUtils.getColorScheme(context).onSurface,
                  fontSize: 14,
                ),
              ),
            ],
          ),
        ),
        Wrap(
          children: List.generate(questions.length, (index) => index)
              .map(
                (index) => hasQuestionAttemptedContainer(
                  attempted: questions[index].attempted,
                  context: context,
                  questionIndex: context
                      .read<ExamOnlineCubit>()
                      .getQuetionIndexById(questions[index].id!),
                ),
              )
              .toList(),
        ),
      ],
    );
  }

  Widget hasQuestionAttemptedContainer({
    required int questionIndex,
    required bool attempted,
    required BuildContext context,
  }) {
    totalAttempted = attempted ? (totalAttempted + 1) : totalAttempted;
    return GestureDetector(
      onTap: () {
        pageController.animateToPage(
          questionIndex,
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeInOut,
        );
        Navigator.of(context).pop();
      },
      child: Container(
        alignment: Alignment.center,
        margin: const EdgeInsets.symmetric(horizontal: 5, vertical: 5),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(10),
          color: attempted
              ? UiUtils.getColorScheme(context).onSecondary
              : UiUtils.getColorScheme(context).error,
        ),
        height: 35,
        width: 35,
        child: Text(
          '${questionIndex + 1}',
          style: TextStyle(color: Theme.of(context).scaffoldBackgroundColor),
        ),
      ),
    );
  }

  Widget setAnsweredAndNotAnsweredCount({
    required BuildContext context,
    required String titleText,
    required int answerCount,
    required Color bgColor,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 15),
      height: 50,
      width: MediaQuery.sizeOf(context).width * 0.90,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(10),
        color: bgColor,
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            UiUtils.getTranslatedLabel(context, titleText),
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: UiUtils.getColorScheme(context).surface,
            ),
          ),
          Text(
            answerCount.toString(),
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: UiUtils.getColorScheme(context).surface,
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 15),
      constraints: BoxConstraints(
        maxHeight: MediaQuery.sizeOf(context).height * 0.95,
      ),
      decoration: const BoxDecoration(
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(20),
          topRight: Radius.circular(20),
        ),
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Stack(
              children: [
                Align(
                  alignment: AlignmentDirectional.centerStart,
                  child: Padding(
                    padding: const EdgeInsets.only(top: 20),
                    child: Text(
                      '${UiUtils.getTranslatedLabel(context, totalQuestionsKey)} : ${context.read<ExamOnlineCubit>().getQuestions().length} ',
                      style: TextStyle(
                        color: UiUtils.getColorScheme(context).onSurface,
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
                Align(
                  alignment: AlignmentDirectional.centerEnd,
                  child: Padding(
                    padding: const EdgeInsets.only(top: 20),
                    child: Text(
                      '${UiUtils.getTranslatedLabel(context, totalMarksKey)} : ${context.read<ExamOnlineCubit>().getTotalMarks()} ',
                      style: TextStyle(
                        color: UiUtils.getColorScheme(context).onSurface,
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
              ],
            ),
            ...context.read<ExamOnlineCubit>().getUniqueQuestionMark().map((
              questionMark,
            ) {
              return buildQuestionAttemptedByMarksContainer(
                context: context,
                questionMark: questionMark,
                questions: context.read<ExamOnlineCubit>().getQuestionsByMark(
                  questionMark,
                ),
              );
            }),
            const Divider(),
            const SizedBox(height: 5),
            setAnsweredAndNotAnsweredCount(
              context: context,
              titleText: unAnsweredKey,
              answerCount:
                  context.read<ExamOnlineCubit>().getTotalQuestions()! -
                  totalAttempted,
              bgColor: UiUtils.getColorScheme(context).error,
            ),
            const SizedBox(height: 5),
            setAnsweredAndNotAnsweredCount(
              context: context,
              titleText: answeredKey,
              answerCount: totalAttempted,
              bgColor: UiUtils.getColorScheme(context).onSecondary,
            ),
            const SizedBox(height: 20),
            BlocBuilder<ExamOnlineCubit, ExamOnlineState>(
              builder: (context, state) {
                return CustomRoundedButton(
                  onTap: () {
                    if (state is ExamOnlineFetchInProgress) {
                      return;
                    }
                    navigateToResultScreen();
                  },
                  widthPercentage: 0.9,
                  backgroundColor: UiUtils.getColorScheme(context).primary,
                  buttonTitle: UiUtils.getTranslatedLabel(context, submitKey),
                  radius: 10,
                  showBorder: false,
                  titleColor: UiUtils.getColorScheme(context).surface,
                  height: 50,
                  child: (state is ExamOnlineFetchInProgress)
                      ? CustomCircularProgressIndicator(
                          indicatorColor: Theme.of(
                            context,
                          ).scaffoldBackgroundColor,
                        )
                      : null,
                );
              },
            ),
            SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
          ],
        ),
      ),
    );
  }
}
