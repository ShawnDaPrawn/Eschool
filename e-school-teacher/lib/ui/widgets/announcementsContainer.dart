import 'package:eschool_teacher/app/routeArguments.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/announcementsCubit.dart';
import 'package:eschool_teacher/cubits/deleteAnnouncementCubit.dart';
import 'package:eschool_teacher/data/models/classSectionDetails.dart';
import 'package:eschool_teacher/data/models/subject.dart';
import 'package:eschool_teacher/data/repositories/announcementRepository.dart';
import 'package:eschool_teacher/ui/widgets/attachmentsBottomsheetContainer.dart';
import 'package:eschool_teacher/ui/widgets/deleteButton.dart';
import 'package:eschool_teacher/ui/widgets/editButton.dart';
import 'package:eschool_teacher/ui/widgets/noDataContainer.dart';
import 'package:eschool_teacher/utils/errorMessageKeysAndCodes.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class AnnouncementsContainer extends StatelessWidget {
  const AnnouncementsContainer({
    required this.classSectionDetails,
    required this.subject,
    super.key,
  });
  final Subject subject;
  final ClassSectionDetails classSectionDetails;

  Widget _buildAnnouncementShimmerLoading(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 25),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12.5),
      width: MediaQuery.sizeOf(context).width * 0.8,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ShimmerLoadingContainer(
                child: CustomShimmerContainer(
                  borderRadius: 4,
                  width: boxConstraints.maxWidth * 0.65,
                ),
              ),
              const SizedBox(height: 10),
              ShimmerLoadingContainer(
                child: CustomShimmerContainer(
                  borderRadius: 3,
                  width: boxConstraints.maxWidth * 0.5,
                ),
              ),
              const SizedBox(height: 20),
              ShimmerLoadingContainer(
                child: CustomShimmerContainer(
                  borderRadius: 3,
                  height: UiUtils.shimmerLoadingContainerDefaultHeight - 2,
                  width: boxConstraints.maxWidth * 0.3,
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildAnnouncementContainer({
    required Announcement announcement,
    required BuildContext context,
    required int index,
    required int totalAnnouncements,
    required bool hasMoreAnnouncements,
    required bool hasMoreAnnouncementsInProgress,
    required bool fetchMoreAnnouncementsFailure,
  }) {
    //show announcement loading container for last announcement container
    if (index == (totalAnnouncements - 1)) {
      //If has more assignment
      if (hasMoreAnnouncements) {
        if (hasMoreAnnouncementsInProgress) {
          return _buildAnnouncementShimmerLoading(context);
        }
        if (fetchMoreAnnouncementsFailure) {
          return Center(
            child: TextButton(
              child: Text(UiUtils.getTranslatedLabel(context, retryKey)),
              onPressed: () {
                context.read<AnnouncementsCubit>().fetchMoreAnnouncements(
                  classSectionId: classSectionDetails.id,
                  subjectId: subject.id,
                  termId: subject.term?.id,
                );
              },
            ),
          );
        }
      }
    }

    return BlocProvider(
      create: (context) => DeleteAnnouncementCubit(AnnouncementRepository()),
      child: Builder(
        builder: (context) {
          return BlocConsumer<DeleteAnnouncementCubit, DeleteAnnouncementState>(
            listener: (context, state) {
              if (state is DeleteAnnouncementSuccess) {
                context.read<AnnouncementsCubit>().deleteAnnouncement(
                  announcement.id,
                );
              } else if (state is DeleteAnnouncementFailure) {
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
                      : UiUtils.getTranslatedLabel(context, unableToDeleteKey),
                  backgroundColor: Theme.of(context).colorScheme.error,
                );
              }
            },
            builder: (context, state) {
              return Opacity(
                opacity: state is DeleteAnnouncementInProgress ? 0.5 : 1.0,
                child: Container(
                  margin: const EdgeInsets.only(bottom: 20),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 20,
                    vertical: 15,
                  ),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.surfaceContainerLow,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  width: MediaQuery.sizeOf(context).width * 0.85,
                  child: LayoutBuilder(
                    builder: (context, boxConstraints) {
                      return Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Expanded(
                                child: Text(
                                  announcement.title,
                                  maxLines: 5,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    height: 1.1,
                                    color: Theme.of(context)
                                        .colorScheme
                                        .onSurface,
                                    fontSize: 15,
                                  ),
                                ),
                              ),
                              EditButton(
                                onTap: () {
                                  if (state is DeleteAnnouncementInProgress) {
                                    return;
                                  }
                                  Navigator.of(context)
                                      .pushNamed<bool?>(
                                        Routes.addOrEditAnnouncement,
                                        arguments:
                                            AddOrEditAnnouncementArguments(
                                              subject: subject,
                                              classSectionDetails:
                                                  classSectionDetails,
                                              announcement: announcement,
                                            ),
                                      )
                                      .then((value) {
                                        if (value != null && value) {
                                          if (context.mounted) {
                                            context
                                                .read<AnnouncementsCubit>()
                                                .fetchAnnouncements(
                                                  classSectionId:
                                                      classSectionDetails.id,
                                                  subjectId: subject.id,
                                                  termId: subject.term?.id,
                                                );
                                          }
                                        }
                                      });
                                },
                              ),
                              const SizedBox(width: 10),
                              DeleteButton(
                                onTap: () {
                                  if (state is DeleteAnnouncementInProgress) {
                                    return;
                                  }
                                  showDialog<bool>(
                                    context: context,
                                    builder: (_) => const ConfirmDeleteDialog(),
                                  ).then((value) {
                                    if (value != null && value) {
                                      if (context.mounted) {
                                        context
                                            .read<DeleteAnnouncementCubit>()
                                            .deleteAnnouncement(
                                              announcement.id,
                                            );
                                      }
                                    }
                                  });
                                },
                              ),
                            ],
                          ),
                          if (announcement.description.isEmpty)
                            const SizedBox()
                          else
                            Text(
                              announcement.description,
                              style: TextStyle(
                                height: 1.2,
                                color: Theme.of(context).colorScheme.onSurface,
                                fontWeight: FontWeight.w400,
                                fontSize: 11.5,
                              ),
                            ),
                          if (announcement.files.isNotEmpty)
                            Padding(
                              padding: const EdgeInsets.only(top: 5),
                              child: GestureDetector(
                                onTap: () {
                                  UiUtils.showBottomSheet(
                                    child: AttachmentBottomsheetContainer(
                                      fromAnnouncementsContainer: true,
                                      studyMaterials: announcement.files,
                                    ),
                                    context: context,
                                  );
                                },
                                child: Text(
                                  '${announcement.files.length} ${UiUtils.getTranslatedLabel(context, attachmentsKey)}',
                                  style: TextStyle(
                                    color: StatusColors.of(context).info,
                                  ),
                                ),
                              ),
                            )
                          else
                            const SizedBox(),
                          SizedBox(
                            height: announcement.files.isNotEmpty ? 0 : 5,
                          ),
                          Row(
                            children: [
                              Icon(
                                Icons.calendar_month_outlined,
                                color: Theme.of(context)
                                    .colorScheme
                                    .onSurfaceVariant
                                    .withValues(alpha: 0.75),
                                size: 14,
                              ),
                              const SizedBox(width: 5),
                              Expanded(
                                child: Text(
                                  UiUtils.getTimeAgo(
                                    context,
                                    date: announcement.createdAt,
                                  ),

                                  style: TextStyle(
                                    color: Theme.of(context)
                                        .colorScheme
                                        .onSurfaceVariant
                                        .withValues(alpha: 0.75),
                                    fontWeight: FontWeight.w400,
                                    fontSize: 10,
                                  ),
                                  textAlign: TextAlign.start,
                                ),
                              ),
                            ],
                          ),
                        ],
                      );
                    },
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
    return BlocBuilder<AnnouncementsCubit, AnnouncementsState>(
      builder: (context, state) {
        if (state is AnnouncementsFetchSuccess) {
          return state.announcements.isEmpty
              ? const NoDataContainer(titleKey: noAnnouncementsKey)
              : Column(
                  children:
                      List.generate(
                            state.announcements.length,
                            (index) => index,
                          )
                          .map(
                            (index) => Animate(
                              effects: customItemFadeAppearanceEffects(),
                              child: _buildAnnouncementContainer(
                                context: context,
                                announcement: state.announcements[index],
                                index: index,
                                totalAnnouncements: state.announcements.length,
                                hasMoreAnnouncements: context
                                    .read<AnnouncementsCubit>()
                                    .hasMore(),
                                hasMoreAnnouncementsInProgress:
                                    state.fetchMoreAnnouncementsInProgress,
                                fetchMoreAnnouncementsFailure:
                                    state.moreAnnouncementsFetchError,
                              ),
                            ),
                          )
                          .toList(),
                );
        }
        if (state is AnnouncementsFetchFailure) {
          return Center(
            child: ErrorContainer(
              errorMessageCode: state.errorMessage,
              onTapRetry: () {
                context.read<AnnouncementsCubit>().fetchAnnouncements(
                  classSectionId: classSectionDetails.id,
                  subjectId: subject.id,
                  termId: subject.term?.id,
                );
              },
            ),
          );
        }

        return Column(
          children: List.generate(
            UiUtils.defaultShimmerLoadingContentCount,
            (index) => index,
          ).map((e) => _buildAnnouncementShimmerLoading(context)).toList(),
        );
      },
    );
  }
}
