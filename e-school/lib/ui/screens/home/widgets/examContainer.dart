import 'package:eschool_session/eschool_session.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/examTabSelectionCubit.dart';
import 'package:eschool/cubits/termsCubit.dart';
import 'package:eschool/data/models/term.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool/ui/widgets/termFilterBottomsheetContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/widgets/customTabBarContainer.dart';
import 'package:eschool/ui/widgets/examOfflineListContainer.dart';
import 'package:eschool/ui/widgets/examOnlineListContainer.dart';
import 'package:eschool/ui/widgets/tabBarBackgroundContainer.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ExamContainer extends StatefulWidget {
  const ExamContainer({super.key, this.childId, this.subjects});
  final int? childId;
  final List<Subject>? subjects;

  @override
  State<ExamContainer> createState() => _ExamContainerState();
}

class _ExamContainerState extends State<ExamContainer> {
  late ValueNotifier<Term?> selectedTermNotifier = ValueNotifier<Term?>(
    context.read<AppConfigurationCubit>().getAppConfiguration().currentTerm,
  );

  bool get doesChildHaveTermInClass {
    if (widget.childId == null) {
      return context.read<AuthCubit>().getStudentDetails().isClassTermOn;
    } else {
      return context
          .read<AuthCubit>()
          .getParentDetails()
          .getChild(widget.childId!)
          .isClassTermOn;
    }
  }

  @override
  void initState() {
    super.initState();
    context.read<TermsCubit>().fetchTerms();
  }

  Widget _buildAppBar(
    BuildContext context,
    ExamTabSelectionState currentState,
  ) {
    return ScreenTopBackgroundContainer(
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Stack(
            clipBehavior: Clip.none,
            children: [
              context.read<AuthCubit>().isParent()
                  ? const CustomBackButton()
                  : const SizedBox(),
              currentState.examFilterTabTitle == offlineKey &&
                      doesChildHaveTermInClass &&
                      selectedTermNotifier.value != null
                  ? Align(
                      alignment: AlignmentDirectional.topEnd,
                      child: Padding(
                        padding: EdgeInsetsDirectional.only(
                          end: UiUtils.screenContentHorizontalPadding,
                        ),
                        child: SvgButton(
                          onTap: () async {
                            final Term? newSelection =
                                await showModalBottomSheet(
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.only(
                                      topLeft: Radius.circular(
                                        UiUtils.bottomSheetTopRadius,
                                      ),
                                      topRight: Radius.circular(
                                        UiUtils.bottomSheetTopRadius,
                                      ),
                                    ),
                                  ),
                                  context: context,
                                  builder: (_) => BlocProvider.value(
                                    value: context.read<TermsCubit>(),
                                    child: TermFilterBottomsheetContainer(
                                      selectedTerm: selectedTermNotifier.value!,
                                    ),
                                  ),
                                );
                            if (newSelection != null) {
                              selectedTermNotifier.value = newSelection;
                            }
                          },
                          svgIconUrl: Assets.filterIcon,
                        ),
                      ),
                    )
                  : const SizedBox(),
              Align(
                alignment: Alignment.topCenter,
                child: Text(
                  UiUtils.getTranslatedLabel(context, examsKey),
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.onInverseSurface,
                    fontSize: UiUtils.screenTitleFontSize,
                  ),
                ),
              ),
              AnimatedAlign(
                curve: UiUtils.tabBackgroundContainerAnimationCurve,
                duration: UiUtils.tabBackgroundContainerAnimationDuration,
                alignment: currentState.examFilterTabTitle == offlineKey
                    ? AlignmentDirectional.centerStart
                    : AlignmentDirectional.centerEnd,
                child: TabBarBackgroundContainer(
                  boxConstraints: boxConstraints,
                ),
              ),
              CustomTabBarContainer(
                boxConstraints: boxConstraints,
                alignment: AlignmentDirectional.centerStart,
                isSelected: currentState.examFilterTabTitle == offlineKey,
                onTap: () {
                  context
                      .read<ExamTabSelectionCubit>()
                      .changeExamFilterTabTitle(offlineKey);
                },
                titleKey: offlineKey,
              ),
              ModuleVisibility(
                module: 'online_exams',
                child: CustomTabBarContainer(
                  boxConstraints: boxConstraints,
                  alignment: AlignmentDirectional.centerEnd,
                  isSelected: currentState.examFilterTabTitle == onlineKey,
                  onTap: () {
                    context
                        .read<ExamTabSelectionCubit>()
                        .changeExamFilterTabTitle(onlineKey);
                  },
                  titleKey: onlineKey,
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<ExamTabSelectionCubit, ExamTabSelectionState>(
      builder: (context, state) {
        return Stack(
          children: [
            context.read<ExamTabSelectionCubit>().isExamOnline() &&
                    allowsSchoolModule(
                      context.watch<MeContextCubit>().state.context,
                      'online_exams',
                    )
                ? ExamOnlineListContainer(
                    childId: widget.childId,
                    subjects: widget.subjects,
                  )
                : ValueListenableBuilder(
                    valueListenable: selectedTermNotifier,
                    builder: (context, value, _) {
                      return ExamOfflineListContainer(
                        key: ValueKey(value?.id),
                        childId: widget.childId,
                        termId: value?.id,
                      );
                    },
                  ),
            Align(
              alignment: Alignment.topCenter,
              child: _buildAppBar(context, state),
            ),
          ],
        );
      },
    );
  }
}
