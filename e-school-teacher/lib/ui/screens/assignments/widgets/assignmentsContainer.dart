import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/assignmentCubit.dart';
import 'package:eschool_teacher/cubits/deleteAssignmentCubit.dart';
// ignore: depend_on_referenced_packages
import 'package:eschool_teacher/data/models/assignment.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/models/subject.dart';
import 'package:eschool_teacher/data/repositories/assignmentRepository.dart';
import 'package:eschool_teacher/ui/screens/assignments/widgets/assignmentDetailsBottomsheetContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/widgets/deleteButton.dart';
import 'package:eschool_teacher/ui/widgets/editButton.dart';
import 'package:eschool_teacher/ui/widgets/noDataContainer.dart';
import 'package:eschool_teacher/utils/errorMessageKeysAndCodes.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class AssignmentsContainer extends StatelessWidget {
  const AssignmentsContainer({
    required this.classSectionDetails,
    required this.subject,
    super.key,
  });
  final ClassSectionDetails classSectionDetails;
  final Subject subject;

  void showAssignmentBottomSheet({
    required BuildContext context,
    required Assignment assignment,
  }) {
    UiUtils.showBottomSheet(
      enableDrag: true,
      child: AssignmentDetailsBottomsheetContainer(assignment: assignment),
      context: context,
    );
  }

  Widget asignmentListtile(Assignment assignment) {
    return BlocProvider<DeleteAssignmentCubit>(
      create: (context) => DeleteAssignmentCubit(AssignmentRepository()),
      child: Builder(
        builder: (context) {
          return BlocConsumer<DeleteAssignmentCubit, DeleteAssignmentState>(
            listener: (context, state) {
              if (state is DeleteAssignmentSuccess) {
                context.read<AssignmentCubit>().deleteAssignment(assignment.id);
              } else if (state is DeleteAssignmentFailure) {
                UiUtils.showBottomToastOverlay(
                  context: context,
                  errorMessage:
                      state.errorMessage !=
                              ErrorMessageKeysAndCode.defaultErrorMessageCode &&
                          state.errorMessage !=
                              ErrorMessageKeysAndCode.internetServerErrorCode
                      ? UiUtils.getTranslatedLabel(
                          context,
                          ErrorMessageKeysAndCode.getErrorMessageKeyFromCode(
                            state.errorMessage,
                          ),
                        )
                      : UiUtils.getTranslatedLabel(
                          context,
                          unableToDeleteAssignmentKey,
                        ),
                  backgroundColor: Theme.of(context).colorScheme.error,
                );
              }
            },
            builder: (context, state) {
              return Padding(
                padding: const EdgeInsets.only(bottom: 20),
                child: GestureDetector(
                  onTap: () {
                    if (state is DeleteAssignmentInProgress) {
                      return;
                    }
                    showAssignmentBottomSheet(
                      context: context,
                      assignment: assignment,
                    );
                  },
                  child: Opacity(
                    opacity: state is DeleteAssignmentInProgress ? 0.5 : 1.0,
                    child: Container(
                      decoration: BoxDecoration(
                        boxShadow: [
                          BoxShadow(
                            color: Theme.of(
                              context,
                            ).colorScheme.shadow.withValues(alpha: 0.05),
                            offset: const Offset(2.5, 2.5),
                            blurRadius: 10,
                          ),
                        ],
                        color: Theme.of(context).colorScheme.surface,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      width: MediaQuery.sizeOf(context).width * 0.85,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 17.5,
                        vertical: 17.5,
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  assignment.name,
                                  maxLines: 5,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    color: Theme.of(
                                      context,
                                    ).colorScheme.onSurface,
                                    fontWeight: FontWeight.w400,
                                    fontSize: 14,
                                  ),
                                ),
                              ),
                              EditButton(
                                onTap: () {
                                  Navigator.of(context)
                                      .pushNamed<bool?>(
                                        Routes.addAssignment,
                                        arguments: {
                                          'editAssignment': true,
                                          'assignment': assignment,
                                        },
                                      )
                                      .then((value) {
                                        if (value != null && value) {
                                          context
                                              .read<AssignmentCubit>()
                                              .fetchAssignment(
                                                classSectionId:
                                                    assignment.classSectionId,
                                                subjectId: assignment.subjectId,
                                                semesterId: assignment
                                                    .subject
                                                    .semester
                                                    ?.id,
                                              );
                                        }
                                      });
                                },
                              ),
                              const SizedBox(width: 10),
                              DeleteButton(
                                onTap: () {
                                  if (state is DeleteAssignmentInProgress) {
                                    return;
                                  }
                                  showDialog<bool>(
                                    context: context,
                                    builder: (_) => const ConfirmDeleteDialog(),
                                  ).then((value) {
                                    if (value != null && value) {
                                      context
                                          .read<DeleteAssignmentCubit>()
                                          .deleteAssignment(
                                            assignmentId: assignment.id,
                                          );
                                    }
                                  });
                                },
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),
                          Text(
                            '${UiUtils.getTranslatedLabel(context, dueDateKey)}: ${UiUtils.formatDateAndTime(assignment.dueDate)}',
                            style: TextStyle(
                              color: Theme.of(context)
                                  .colorScheme
                                  .onSurfaceVariant
                                  .withValues(alpha: 0.6),
                              fontWeight: FontWeight.w400,
                              fontSize: 11,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }

  Widget _buildInformationShimmerLoadingContainer({
    required BuildContext context,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 15),
        width: MediaQuery.sizeOf(context).width * 0.85,
        child: LayoutBuilder(
          builder: (context, boxConstraints) {
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                ShimmerLoadingContainer(
                  child: CustomShimmerContainer(
                    margin: EdgeInsetsDirectional.only(
                      end: boxConstraints.maxWidth * 0.7,
                    ),
                  ),
                ),
                const SizedBox(height: 5),
                ShimmerLoadingContainer(
                  child: CustomShimmerContainer(
                    margin: EdgeInsetsDirectional.only(
                      end: boxConstraints.maxWidth * 0.5,
                    ),
                  ),
                ),
                const SizedBox(height: 15),
                ShimmerLoadingContainer(
                  child: CustomShimmerContainer(
                    margin: EdgeInsetsDirectional.only(
                      end: boxConstraints.maxWidth * 0.7,
                    ),
                  ),
                ),
                const SizedBox(height: 5),
                ShimmerLoadingContainer(
                  child: CustomShimmerContainer(
                    margin: EdgeInsetsDirectional.only(
                      end: boxConstraints.maxWidth * 0.5,
                    ),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AssignmentCubit, AssignmentState>(
      bloc: context.read<AssignmentCubit>(),
      builder: (context, state) {
        if (state is AssignmentsFetchSuccess) {
          return state.assignment.isEmpty
              ? const NoDataContainer(titleKey: noAssignmentsKey)
              : Column(
                  children: state.assignment
                      .map(
                        (assignment) => Animate(
                          effects: customItemFadeAppearanceEffects(),
                          child: asignmentListtile(assignment),
                        ),
                      )
                      .toList(),
                );
        }
        if (state is AssignmentFetchFailure) {
          return Center(
            child: ErrorContainer(
              errorMessageCode: state.errorMessage,
              onTapRetry: () {
                context.read<AssignmentCubit>().fetchAssignment(
                  classSectionId: classSectionDetails.id,
                  subjectId: subject.id,
                  semesterId: subject.semester?.id,
                );
              },
            ),
          );
        }
        return Column(
          children: List.generate(5, (index) {
            return _buildInformationShimmerLoadingContainer(context: context);
          }),
        );
      },
    );
  }
}
