import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/deleteTopicCubit.dart';
import 'package:eschool_teacher/cubits/topicsCubit.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/models/lesson.dart';
import 'package:eschool_teacher/data/models/subject.dart';
import 'package:eschool_teacher/data/models/topic.dart';
import 'package:eschool_teacher/data/repositories/topicRepository.dart';
import 'package:eschool_teacher/ui/widgets/attachmentsBottomsheetContainer.dart';
import 'package:eschool_teacher/ui/widgets/confirmDeleteDialog.dart';
import 'package:eschool_teacher/ui/widgets/customShimmerContainer.dart';
import 'package:eschool_teacher/ui/widgets/deleteButton.dart';
import 'package:eschool_teacher/ui/widgets/editButton.dart';
import 'package:eschool_teacher/ui/widgets/errorContainer.dart';
import 'package:eschool_teacher/ui/widgets/noDataContainer.dart';
import 'package:eschool_teacher/ui/widgets/shimmerLoadingContainer.dart';
import 'package:eschool_teacher/utils/animationConfiguration.dart';
import 'package:eschool_teacher/utils/errorMessageKeysAndCodes.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class TopicsContainer extends StatelessWidget {
  const TopicsContainer({
    required this.classSectionDetails,
    required this.lesson,
    required this.subject,
    super.key,
  });
  final Subject subject;
  final Lesson lesson;
  final ClassSectionDetails classSectionDetails;

  Widget _buildTopicDetailsShimmerContainer(BuildContext context) {
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

  Widget _buildTopicDetailsContainer({
    required Topic topic,
    required BuildContext context,
  }) {
    return BlocProvider<DeleteTopicCubit>(
      create: (context) => DeleteTopicCubit(TopicRepository()),
      child: Builder(
        builder: (context) {
          return BlocConsumer<DeleteTopicCubit, DeleteTopicState>(
            listener: (context, state) {
              if (state is DeleteTopicSuccess) {
                context.read<TopicsCubit>().deleteTopic(topic.id);
              } else if (state is DeleteTopicFailure) {
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
                      : '${UiUtils.getTranslatedLabel(context, unableToDeleteTopicKey)} ${topic.name}',
                  backgroundColor: Theme.of(context).colorScheme.error,
                );
              }
            },
            builder: (context, state) {
              return Padding(
                padding: const EdgeInsets.only(bottom: 20),
                child: Opacity(
                  opacity: state is DeleteTopicInProgress ? 0.5 : 1.0,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 20,
                      vertical: 15,
                    ),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surfaceContainerLow,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    width: MediaQuery.sizeOf(context).width * 0.85,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Text(
                              UiUtils.getTranslatedLabel(context, topicNameKey),
                              style: TextStyle(
                                color: Theme.of(
                                  context,
                                ).colorScheme.onSurfaceVariant,
                                fontWeight: FontWeight.w400,
                                fontSize: 12,
                              ),
                              textAlign: TextAlign.start,
                            ),
                            const Spacer(),
                            EditButton(
                              onTap: () {
                                if (state is DeleteTopicInProgress) {
                                  return;
                                }
                                Navigator.of(context)
                                    .pushNamed<bool?>(
                                      Routes.addOrEditTopic,
                                      arguments: {
                                        'classSectionDetails':
                                            classSectionDetails,
                                        'subject': subject,
                                        'lesson': lesson,
                                        'topic': topic,
                                      },
                                    )
                                    .then((value) {
                                      if (value != null && value) {
                                        if (context.mounted) {
                                          context
                                              .read<TopicsCubit>()
                                              .fetchTopics(lessonId: lesson.id);
                                        }
                                      }
                                    });
                              },
                            ),
                            const SizedBox(width: 10),
                            DeleteButton(
                              onTap: () {
                                if (state is DeleteTopicInProgress) {
                                  return;
                                }
                                showDialog<bool>(
                                  context: context,
                                  builder: (_) => const ConfirmDeleteDialog(),
                                ).then((value) {
                                  if (value != null && value) {
                                    if (context.mounted) {
                                      context
                                          .read<DeleteTopicCubit>()
                                          .deleteTopic(topicId: topic.id);
                                    }
                                  }
                                });
                              },
                            ),
                          ],
                        ),
                        const SizedBox(height: 2.5),
                        Text(
                          topic.name,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.onSurface,
                            fontWeight: FontWeight.w600,
                            fontSize: 14,
                          ),
                          textAlign: TextAlign.start,
                        ),
                        const SizedBox(height: 15),
                        Text(
                          UiUtils.getTranslatedLabel(
                            context,
                            topicDescriptionKey,
                          ),
                          style: TextStyle(
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurfaceVariant,
                            fontWeight: FontWeight.w400,
                            fontSize: 12,
                          ),
                          textAlign: TextAlign.start,
                        ),
                        const SizedBox(height: 2.5),
                        Text(
                          topic.description,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.onSurface,
                            fontWeight: FontWeight.w600,
                            fontSize: 14,
                          ),
                          textAlign: TextAlign.start,
                        ),
                        if (topic.studyMaterials.isNotEmpty)
                          Padding(
                            padding: const EdgeInsets.only(top: 5),
                            child: GestureDetector(
                              onTap: () {
                                UiUtils.showBottomSheet(
                                  child: AttachmentBottomsheetContainer(
                                    fromAnnouncementsContainer: false,
                                    studyMaterials: topic.studyMaterials,
                                  ),
                                  context: context,
                                );
                              },
                              child: Text(
                                '${topic.studyMaterials.length} ${UiUtils.getTranslatedLabel(context, attachmentsKey)}',
                                style: TextStyle(
                                  color: StatusColors.of(context).info,
                                ),
                              ),
                            ),
                          )
                        else
                          const SizedBox(),
                      ],
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

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<TopicsCubit, TopicsState>(
      builder: (context, state) {
        if (state is TopicsFetchSuccess) {
          return state.topics.isEmpty
              ? const NoDataContainer(titleKey: noTopicsKey)
              : Column(
                  children: state.topics
                      .map(
                        (topic) => Animate(
                          effects: customItemFadeAppearanceEffects(),
                          child: _buildTopicDetailsContainer(
                            topic: topic,
                            context: context,
                          ),
                        ),
                      )
                      .toList(),
                );
        }
        if (state is TopicsFetchFailure) {
          return Center(
            child: ErrorContainer(
              errorMessageCode: state.errorMessage,
              onTapRetry: () {
                context.read<TopicsCubit>().fetchTopics(lessonId: lesson.id);
              },
            ),
          );
        }
        return Column(
          children: List.generate(
            UiUtils.defaultShimmerLoadingContentCount,
            (index) => index,
          ).map((e) => _buildTopicDetailsShimmerContainer(context)).toList(),
        );
      },
    );
  }
}
