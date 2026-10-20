import 'dart:async';

import 'package:eschool/app/routes.dart';
import 'package:eschool/data/models/examsOnline.dart';
import 'package:eschool/ui/screens/exam/onlineExam/cubits/examOnlineCubit.dart';
import 'package:eschool/ui/screens/exam/onlineExam/widgets/examQuestionStatusBottomSheetContainer.dart';
import 'package:eschool/ui/screens/exam/onlineExam/widgets/examTimerContainer.dart';
import 'package:eschool/ui/screens/exam/onlineExam/widgets/optionContainer.dart';
import 'package:eschool/ui/screens/exam/onlineExam/widgets/questionContainer.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool/ui/styles/colors.dart';
import 'package:eschool/ui/widgets/customBackButton.dart';
import 'package:eschool/ui/widgets/customRoundedButton.dart';
import 'package:eschool/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:lottie/lottie.dart';
import 'package:wakelock_plus/wakelock_plus.dart';

class ExamOnlineScreen extends StatefulWidget {
  const ExamOnlineScreen({required this.exam, super.key});
  final ExamsOnline exam;

  @override
  ExamOnlineScreenState createState() => ExamOnlineScreenState();
  static Route route(RouteSettings routeSettings) {
    final arguments = routeSettings.arguments! as Map<String, dynamic>;
    return CupertinoPageRoute(
      builder: (_) => ExamOnlineScreen(exam: arguments['exam']),
    );
  }
}

class ExamOnlineScreenState extends State<ExamOnlineScreen>
    with WidgetsBindingObserver {
  final GlobalKey<ExamTimerContainerState> timerKey =
      GlobalKey<ExamTimerContainerState>();
  late PageController pageController = PageController();

  bool isExitDialogOpen = false;
  bool isExamQuestionStatusBottomsheetOpen = false;
  bool isExamCompleted = false;
  bool isSubmissionInProgress = false;

  int currentQuestionIndex = 0;
  Map<int, List<int>> selectedAnswersWithQuestionId = {};

  Timer? canGiveExamAgainTimer;
  bool canGiveExamAgain = true;

  int canGiveExamAgainTimeInSeconds = 5;

  @override
  void initState() {
    super.initState();
    newSubmittedId.clear();
    Future.delayed(Duration.zero, () {
      timerKey.currentState?.startTimer();
    });

    WakelockPlus.enable();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    canGiveExamAgainTimer?.cancel();
    WakelockPlus.disable();
    super.dispose();
  }

  void setCanGiveExamTimer() {
    canGiveExamAgainTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (canGiveExamAgainTimeInSeconds == 0) {
        timer.cancel();

        //can give exam again false
        canGiveExamAgain = false;

        //show exam complete
        if (!isExamCompleted) submitAnswers();
        //submit only if not submitted before
      } else {
        canGiveExamAgainTimeInSeconds--;
      }
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.paused) {
      setCanGiveExamTimer();
    } else if (state == AppLifecycleState.resumed) {
      canGiveExamAgainTimer?.cancel();
      //if user can give exam again
      if (canGiveExamAgain) {
        canGiveExamAgainTimeInSeconds = 5;
      }
    }
  }

  Future<bool> onBackPress() {
    isExitDialogOpen = true;
    if (!isExamCompleted) {
      showDialog(
        context: context,
        builder: (context) => AlertDialog(
          backgroundColor: Colors.white,
          title: Text(UiUtils.getTranslatedLabel(context, quitExamKey)),
          content: Text(
            UiUtils.getTranslatedLabel(context, quitExamDisclaimerKey),
          ),
          actions: [
            CupertinoButton(
              child: Text(
                UiUtils.getTranslatedLabel(context, yesKey),
                style: TextStyle(color: Theme.of(context).colorScheme.error),
              ),
              onPressed: () {
                submitAnswers();
                Navigator.of(context).pop();
              },
            ),
            CupertinoButton(
              child: Text(UiUtils.getTranslatedLabel(context, noKey)),
              onPressed: () {
                Navigator.of(context).pop();
              },
            ),
          ],
        ),
      ).then((value) {
        isExitDialogOpen = false;
      });
    }
    return Future.value(false);
  }

  Widget buildOnlineExamAppbar(BuildContext context) {
    return ScreenTopBackgroundContainer(
      heightPercentage: UiUtils.appBarMediumHeightPercentage,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          CustomBackButton(onTap: onBackPress),
          Align(
            alignment: Alignment.topCenter,
            child: Text(
              widget.exam.subject.showType
                  ? widget.exam.subject.subjectNameWithType
                  : widget.exam.subject.name,
              style: TextStyle(
                color: Theme.of(context).scaffoldBackgroundColor,
                fontSize: UiUtils.screenTitleFontSize,
              ),
            ),
          ),
          Align(
            alignment: AlignmentDirectional.topEnd,
            child: Padding(
              padding: const EdgeInsetsDirectional.only(end: 25),
              child: ExamTimerContainer(
                navigateToResultScreen: finishExamOnline,
                examDurationInMinutes: widget.exam.duration,
                key: timerKey,
              ),
            ),
          ),
          Align(
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  widget.exam.title,
                  style: TextStyle(
                    color: UiUtils.getColorScheme(context).surface,
                    fontSize: UiUtils.screenSubTitleFontSize,
                  ),
                ),
                Container(
                  margin: const EdgeInsets.symmetric(horizontal: 4),
                  width: 5,
                  height: 5,
                  decoration: BoxDecoration(
                    color: UiUtils.getColorScheme(context).surface,
                    borderRadius: BorderRadius.circular(3),
                  ),
                ),
                Text(
                  '${widget.exam.totalMarks} ${UiUtils.getTranslatedLabel(context, marksKey)}',
                  style: TextStyle(
                    color: UiUtils.getColorScheme(context).surface,
                    fontSize: UiUtils.screenSubTitleFontSize,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void showExamQuestionStatusBottomSheet() {
    isExamQuestionStatusBottomsheetOpen = true;
    showModalBottomSheet(
      isScrollControlled: true,
      elevation: 5,
      context: context,
      isDismissible: !isSubmissionInProgress,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(25),
          topRight: Radius.circular(25),
        ),
      ),
      builder: (context) {
        return ExamQuestionStatusBottomSheetContainer(
          navigateToResultScreen: finishExamOnline,
          pageController: pageController,
        );
      },
    );
  }

  void submitAnswers() {
    //set Answer with each questions
    context.read<ExamOnlineCubit>().getQuestions().forEach((element) {
      selectedAnswersWithQuestionId[element.id!] =
          element.submittedAnswerId ?? [0];
    });
    context.read<ExamOnlineCubit>().setExamOnlineAnswers(
      examId: widget.exam.id,
      selectedAnswersWithQuestionId: selectedAnswersWithQuestionId,
    );
  }

  void finishExamOnline() {
    Future.delayed(Duration.zero, () {
      timerKey.currentState?.cancelTimer();
    });

    if (isExamQuestionStatusBottomsheetOpen && !isSubmissionInProgress) {
      Navigator.of(context).pop();
    }
    if (isExitDialogOpen) {
      Navigator.of(context).pop();
    }
    if (!isExamCompleted) {
      submitAnswers();
    }
  }

  Widget buildBottomButtons() {
    return BlocBuilder<ExamOnlineCubit, ExamOnlineState>(
      builder: (context, state) {
        if (state is ExamOnlineFetchSuccess) {
          return Container(
            decoration: const BoxDecoration(
              color: pageBackgroundColor,
              boxShadow: [
                BoxShadow(
                  color: Colors.black12,
                  offset: Offset(0, -2),
                  blurRadius: 2,
                ),
              ],
            ),
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const SizedBox(width: 25),
                Expanded(
                  child: CustomRoundedButton(
                    onTap: () {
                      showExamQuestionStatusBottomSheet();
                    },
                    widthPercentage: 0.5,
                    height: 40,
                    textAlign: TextAlign.center,
                    fontWeight: FontWeight.bold,
                    backgroundColor: Colors.transparent,
                    buttonTitle: UiUtils.getTranslatedLabel(
                      context,
                      viewDetailsKey,
                    ),
                    titleColor: UiUtils.getColorScheme(context).primary,
                    showBorder: true,
                    borderColor: UiUtils.getColorScheme(context).primary,
                  ),
                ),
                const SizedBox(width: 50),
                GestureDetector(
                  onTap: () {
                    if (currentQuestionIndex != 0) {
                      pageController.previousPage(
                        duration: const Duration(milliseconds: 500),
                        curve: Curves.ease,
                      );
                    }
                  },
                  child: Container(
                    width: MediaQuery.sizeOf(context).width * 0.1,
                    height: MediaQuery.sizeOf(context).width * 0.12,
                    padding: const EdgeInsetsDirectional.only(start: 4),
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: currentQuestionIndex != 0
                          ? UiUtils.getColorScheme(context).primary
                          : UiUtils.getColorScheme(
                              context,
                            ).primary.withValues(alpha: 0.5),
                    ),
                    alignment: Alignment.center,
                    child: Icon(
                      Icons.arrow_back_ios,
                      color: UiUtils.getColorScheme(context).surface,
                      size: 18,
                    ),
                  ),
                ),
                const SizedBox(width: 25),
                GestureDetector(
                  onTap: () {
                    if (currentQuestionIndex != state.questions.length - 1) {
                      pageController.nextPage(
                        duration: const Duration(milliseconds: 500),
                        curve: Curves.ease,
                      );
                    }
                  },
                  child: Container(
                    width: MediaQuery.sizeOf(context).width * 0.1,
                    height: MediaQuery.sizeOf(context).width * 0.1,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: currentQuestionIndex != state.questions.length - 1
                          ? UiUtils.getColorScheme(context).primary
                          : UiUtils.getColorScheme(
                              context,
                            ).primary.withValues(alpha: 0.5),
                    ),
                    alignment: Alignment.center,
                    child: Icon(
                      Icons.arrow_forward_ios,
                      color: UiUtils.getColorScheme(context).surface,
                      size: 18,
                    ),
                  ),
                ),
                const SizedBox(width: 25),
              ],
            ),
          );
        } else {
          return const SizedBox.shrink();
        }
      },
    );
  }

  void submitAnswer(List<int> submittedAnswerIds) {
    context.read<ExamOnlineCubit>().updateQuestionWithAnswer(
      context.read<ExamOnlineCubit>().getQuestions()[currentQuestionIndex].id!,
      submittedAnswerIds,
    );
  }

  Widget buildPageView() {
    return BlocBuilder<ExamOnlineCubit, ExamOnlineState>(
      builder: (context, state) {
        if (state is ExamOnlineFetchSuccess) {
          return PageView.builder(
            onPageChanged: (index) {
              currentQuestionIndex = index;
              newSubmittedId.clear();
              setState(() {});
            },
            controller: pageController,
            itemCount: state.questions.length,
            allowImplicitScrolling: true,
            itemBuilder: (context, index) {
              return SingleChildScrollView(
                padding: EdgeInsets.only(
                  top: UiUtils.getScrollViewTopPadding(
                    context: context,
                    appBarHeightPercentage:
                        UiUtils.appBarMediumHeightPercentage,
                  ),
                  bottom: MediaQuery.sizeOf(context).height * 0.06,
                ),
                child: Column(
                  children: [
                    QuestionContainer(
                      key: PageStorageKey('question_$index'),
                      isMathQuestion: state.questions[index].questionType == 1,
                      questionColor: UiUtils.getColorScheme(context).secondary,
                      questionNumber: index + 1,
                      question: state.questions[index],
                      note: state.questions[index].note,
                    ),
                    (state.questions[index].correctAnswer!.length > 1)
                        ? Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 20),
                            child: Row(
                              children: [
                                Flexible(
                                  child: Text(
                                    '${UiUtils.getTranslatedLabel(context, noteKey)} ${UiUtils.getTranslatedLabel(context, selectKey)} ${state.questions[index].correctAnswer!.length} ${UiUtils.getTranslatedLabel(context, examMultipleAnsNoteKey)}',
                                    style: TextStyle(
                                      color: UiUtils.getColorScheme(
                                        context,
                                      ).onSurface,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          )
                        : const SizedBox.shrink(),
                    const SizedBox(height: 25),
                    ...state.questions[index].answerOptions!.map(
                      (option) => OptionContainer(
                        key: PageStorageKey('option_$index${option.id}'),
                        quizType: state.questions[index].questionType!,
                        constraints: BoxConstraints(
                          maxWidth: MediaQuery.sizeOf(context).width * 0.85,
                          maxHeight:
                              MediaQuery.sizeOf(context).height *
                              UiUtils.questionContainerHeightPercentage,
                        ),
                        answerOption: option,
                        submittedAnswerId: state
                            .questions[index]
                            .submittedAnswerId!
                            .toString(),
                        submitAnswer: submitAnswer,
                        totalAnswers:
                            state.questions[index].correctAnswer!.length,
                      ),
                    ),
                    const SizedBox(height: 50),
                  ],
                ),
              );
            },
          );
        }
        return const SizedBox.shrink();
      },
    );
  }

  Widget buildExamCompleteDialog() {
    isExamCompleted = true;
    return Container(
      alignment: Alignment.center,
      color: UiUtils.getColorScheme(context).secondary.withValues(alpha: 0.5),
      child: AlertDialog(
        backgroundColor: Colors.white,
        content: Column(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Lottie.asset(
              'assets/animations/payment_successful.json',
              animate: true,
            ),
            Text(
              UiUtils.getTranslatedLabel(context, examCompletedKey),
              textAlign: TextAlign.center,
              style: TextStyle(
                color: UiUtils.getColorScheme(context).secondary,
              ),
            ),
          ],
        ),
        actionsAlignment: MainAxisAlignment.center,
        actions: [
          CustomRoundedButton(
            backgroundColor: UiUtils.getColorScheme(context).primary,
            buttonTitle: UiUtils.getTranslatedLabel(context, homeKey),
            titleColor: Theme.of(context).scaffoldBackgroundColor,
            showBorder: false,
            widthPercentage: 0.3,
            height: 45,
            onTap: () {
              //goto 1st tab [Home] in bottomNavigatonbar
              Navigator.of(context).popUntil((route) => route.isFirst);
              HomeScreen.homeScreenKey.currentState?.changeBottomNavItem(0);
            },
          ),
          CustomRoundedButton(
            backgroundColor: Theme.of(context).scaffoldBackgroundColor,
            buttonTitle: UiUtils.getTranslatedLabel(context, resultKey),
            titleColor: UiUtils.getColorScheme(context).primary,
            showBorder: true,
            borderColor: UiUtils.getColorScheme(context).primary,
            widthPercentage: 0.3,
            height: 45,
            onTap: () {
              Navigator.pushReplacementNamed(
                context,
                Routes.resultOnline,
                arguments: {
                  'examId': widget.exam.id,
                  'examName': widget.exam.title,
                  'subjectName': widget.exam.subject.showType
                      ? widget.exam.subject.subjectNameWithType
                      : widget.exam.subject.name,
                },
              );
            },
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, dynamic result) async {
        if (didPop) {
          return;
        }
        final shouldPop = await onBackPress();
        if (shouldPop && context.mounted) {
          Navigator.of(context).pop();
        }
      },
      child: Scaffold(
        floatingActionButton: buildBottomButtons(),
        //bottom center button
        floatingActionButtonLocation:
            FloatingActionButtonLocation.miniCenterDocked,
        body: Stack(
          children: [
            buildPageView(),
            buildOnlineExamAppbar(context),
            BlocConsumer<ExamOnlineCubit, ExamOnlineState>(
              listener: (context, state) {
                if (state is ExamOnlineAnswerSubmissionFail) {
                  isSubmissionInProgress = false;
                  UiUtils.showCustomSnackBar(
                    context: context,
                    errorMessage: UiUtils.getErrorMessageFromErrorCode(
                      context,
                      state.exception,
                    ),
                    backgroundColor: UiUtils.getColorScheme(context).error,
                  );
                  Navigator.of(context).pop();
                }
                if (state is ExamOnlineAnswerSubmitted) {
                  isExamQuestionStatusBottomsheetOpen = true;
                  isSubmissionInProgress = false;
                }
                if (state is ExamOnlineFetchInProgress) {
                  isSubmissionInProgress = true;
                }
              },
              builder: (context, state) {
                if (state is ExamOnlineAnswerSubmitted) {
                  return buildExamCompleteDialog();
                }
                if (isSubmissionInProgress) {
                  return const Center(child: CircularProgressIndicator());
                }
                return const SizedBox.shrink();
              },
            ),
          ],
        ),
      ),
    );
  }
}
