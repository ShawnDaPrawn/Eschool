import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/examTabSelectionCubit.dart';
import 'package:eschool/cubits/semestersCubit.dart';
import 'package:eschool/data/models/semester.dart';
import 'package:eschool/data/models/subject.dart';
import 'package:eschool/ui/widgets/semesterFilterBottomsheetContainer.dart';
import 'package:eschool/ui/widgets/customBackButton.dart';
import 'package:eschool/ui/widgets/customTabBarContainer.dart';
import 'package:eschool/ui/widgets/examOfflineListContainer.dart';
import 'package:eschool/ui/widgets/examOnlineListContainer.dart';
import 'package:eschool/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool/ui/widgets/svgButton.dart';
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
  late ValueNotifier<Semester?> selectedSemesterNotifier =
      ValueNotifier<Semester?>(
        context
            .read<AppConfigurationCubit>()
            .getAppConfiguration()
            .currentSemester,
      );

  bool get doesChildHaveSemesterInClass {
    if (widget.childId == null) {
      return context.read<AuthCubit>().getStudentDetails().isClassSemesterOn;
    } else {
      return context
          .read<AuthCubit>()
          .getParentDetails()
          .getChild(widget.childId!)
          .isClassSemesterOn;
    }
  }

  @override
  void initState() {
    super.initState();
    context.read<SemestersCubit>().fetchSemesters();
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
                      doesChildHaveSemesterInClass &&
                      selectedSemesterNotifier.value != null
                  ? Align(
                      alignment: AlignmentDirectional.topEnd,
                      child: Padding(
                        padding: EdgeInsetsDirectional.only(
                          end: UiUtils.screenContentHorizontalPadding,
                        ),
                        child: SvgButton(
                          onTap: () async {
                            final Semester? newSelection =
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
                                    value: context.read<SemestersCubit>(),
                                    child: SemesterFilterBottomsheetContainer(
                                      selectedSemester:
                                          selectedSemesterNotifier.value!,
                                    ),
                                  ),
                                );
                            if (newSelection != null) {
                              selectedSemesterNotifier.value = newSelection;
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
                    color: Theme.of(context).scaffoldBackgroundColor,
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
              CustomTabBarContainer(
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
            context.read<ExamTabSelectionCubit>().isExamOnline()
                ? ExamOnlineListContainer(
                    childId: widget.childId,
                    subjects: widget.subjects,
                  )
                : ValueListenableBuilder(
                    valueListenable: selectedSemesterNotifier,
                    builder: (context, value, _) {
                      return ExamOfflineListContainer(
                        key: ValueKey(value?.id),
                        childId: widget.childId,
                        semesterId: value?.id,
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
