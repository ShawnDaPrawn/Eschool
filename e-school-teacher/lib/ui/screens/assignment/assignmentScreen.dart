import 'package:eschool_teacher/cubits/editreviewassignmetcubit.dart';
import 'package:eschool_teacher/cubits/reviewassignmentcubit.dart';
import 'package:eschool_teacher/data/models/assignment.dart';
import 'package:eschool_teacher/data/models/reviewAssignmentssubmition.dart';
import 'package:eschool_teacher/data/repositories/reviewAssignmentRepository.dart';
import 'package:eschool_teacher/ui/screens/assignment/widgets/acceptAssignmentBottomsheetContainer.dart';
import 'package:eschool_teacher/ui/screens/assignment/widgets/rejectAssignmentBottomsheetContainer.dart';
import 'package:eschool_teacher/ui/screens/chat/widget/messageItemComponents.dart';
import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:eschool_teacher/ui/widgets/attachmentsBottomsheetContainer.dart';
import 'package:eschool_teacher/ui/widgets/customAppbar.dart';
import 'package:eschool_teacher/ui/widgets/customRefreshIndicator.dart';
import 'package:eschool_teacher/ui/widgets/customShimmerContainer.dart';
import 'package:eschool_teacher/ui/widgets/customUserProfileImageWidget.dart';
import 'package:eschool_teacher/ui/widgets/errorContainer.dart';
import 'package:eschool_teacher/ui/widgets/shimmerLoadingContainer.dart';
import 'package:eschool_teacher/utils/animationConfiguration.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:url_launcher/url_launcher.dart';

class AssignmentScreen extends StatefulWidget {

  const AssignmentScreen({required this.assignment, super.key});
  final Assignment assignment;

  static Route<dynamic> route(RouteSettings routeSettings) {
    final arguments = routeSettings.arguments! as Map<String, dynamic>;
    return CupertinoPageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider<ReviewAssignmentCubit>(
            create: (context) =>
                ReviewAssignmentCubit(ReviewAssignmentRepository()),
          ),
        ],
        child: AssignmentScreen(assignment: arguments['assignment']),
      ),
    );
  }

  @override
  State<AssignmentScreen> createState() => _AssignmentScreenState();
}

class _AssignmentScreenState extends State<AssignmentScreen> {
  late String _currentlySelectedAssignmentFilter = allKey;

  final List<String> _assignmentFilters = [
    allKey,
    submittedKey,
    reSubmittedKey,
    acceptedKey,
    rejectedKey,
    //  pendingKey
  ];

  @override
  void initState() {
    fetchReviewAssignment();
    super.initState();
  }

  void fetchReviewAssignment() {
    context.read<ReviewAssignmentCubit>().fetchReviewAssignment(
      assignmentId: widget.assignment.id,
    );
  }

  void openRejectAssignmentBottomsheet(
    ReviewAssignmentsSubmission reviewAssignment,
  ) {
    UiUtils.showBottomSheet(
      child: BlocProvider<EditReviewAssignmetCubit>(
        create: (context) =>
            EditReviewAssignmetCubit(ReviewAssignmentRepository()),
        child: RejectAssignmentBottomsheetContainer(
          assignment: widget.assignment,
          reviewAssignment: reviewAssignment,
        ),
      ),
      context: context,
    ).then((value) {
      if (value != null) {
        if (context.mounted) {
          context.read<ReviewAssignmentCubit>().updateReviewAssignmet(
            updatedReviewAssignmentSubmition: value,
          );
        }
      }
    });
  }

  void openAcceptAssignmentBottomsheet(
    ReviewAssignmentsSubmission reviewAssignmet,
  ) {
    UiUtils.showBottomSheet(
      child: BlocProvider<EditReviewAssignmetCubit>(
        create: (context) =>
            EditReviewAssignmetCubit(ReviewAssignmentRepository()),
        child: AcceptAssignmentBottomsheetContainer(
          assignment: widget.assignment,
          reviewAssignment: reviewAssignmet,
        ),
      ),
      context: context,
    ).then((value) {
      if (value != null) {
        if (context.mounted) {
          context.read<ReviewAssignmentCubit>().updateReviewAssignmet(
            updatedReviewAssignmentSubmition: value,
          );
        }
      }
    });
  }

  Widget _buildAppbar() {
    return Align(
      alignment: Alignment.topCenter,
      child: CustomAppBar(title: widget.assignment.name),
    );
  }

  Widget _buildInformationShimmerLoadingContainer() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 15),
        width: MediaQuery.sizeOf(context).width * 0.8,
        child: LayoutBuilder(
          builder: (context, boxConstraints) {
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                ShimmerLoadingContainer(
                  child: CustomShimmerContainer(
                    margin: EdgeInsetsDirectional.only(
                      end: boxConstraints.maxWidth * 0.5,
                    ),
                    width: boxConstraints.maxWidth,
                  ),
                ),
                const SizedBox(height: 5),
                ShimmerLoadingContainer(
                  child: CustomShimmerContainer(
                    width: boxConstraints.maxWidth,
                    margin: EdgeInsetsDirectional.only(
                      end: boxConstraints.maxWidth * 0.1,
                    ),
                  ),
                ),
                const SizedBox(height: 15),
                ShimmerLoadingContainer(
                  child: CustomShimmerContainer(
                    width: boxConstraints.maxWidth,
                    margin: EdgeInsetsDirectional.only(
                      end: boxConstraints.maxWidth * 0.5,
                    ),
                  ),
                ),
                const SizedBox(height: 5),
                ShimmerLoadingContainer(
                  child: CustomShimmerContainer(
                    width: boxConstraints.maxWidth,
                    margin: EdgeInsetsDirectional.only(
                      end: boxConstraints.maxWidth * 0.1,
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

  Widget _buildAssignmentFilterContainer(
    String title,
    List<ReviewAssignmentsSubmission> reviewAssignment,
  ) {
    final int totalNumberAssignment = title == allKey
        ? reviewAssignment.length
        : title == submittedKey
        ? reviewAssignment.where((element) => element.status == 0).length
        : title == acceptedKey
        ? reviewAssignment.where((element) => element.status == 1).length
        : title == rejectedKey
        ? reviewAssignment.where((element) => element.status == 2).length
        : title == reSubmittedKey
        ? reviewAssignment.where((element) => element.status == 3).length
        : 0;
    return InkWell(
      onTap: () {
        setState(() {
          _currentlySelectedAssignmentFilter = title;
        });
      },
      borderRadius: BorderRadius.circular(5),
      child: Container(
        margin: const EdgeInsets.only(right: 5),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(5),
          color: _currentlySelectedAssignmentFilter == title
              ? Theme.of(context).colorScheme.primary
              : Colors.transparent,
        ),
        padding: const EdgeInsets.symmetric(horizontal: 10),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              UiUtils.getTranslatedLabel(context, title),
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: _currentlySelectedAssignmentFilter == title
                    ? Theme.of(context).scaffoldBackgroundColor
                    : Theme.of(context).colorScheme.primary,
              ),
            ),
            const SizedBox(width: 2.5),
            Text(
              '($totalNumberAssignment)',
              style: TextStyle(
                fontSize: 11.5,
                color: _currentlySelectedAssignmentFilter == title
                    ? Theme.of(context).scaffoldBackgroundColor
                    : Theme.of(context).colorScheme.primary,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAssignmentSubmissionFilters(
    List<ReviewAssignmentsSubmission> reviewAssignment,
  ) {
    return Transform.translate(
      offset: const Offset(0, -5),
      child: SizedBox(
        height: 30,
        child: ListView.builder(
          padding: EdgeInsets.only(
            left: MediaQuery.sizeOf(context).width * 0.05,
          ),
          itemCount: _assignmentFilters.length,
          itemBuilder: (context, index) {
            return _buildAssignmentFilterContainer(
              _assignmentFilters[index],
              reviewAssignment,
            );
          },
          scrollDirection: Axis.horizontal,
        ),
      ),
    );
  }

  Widget _buildTextSubmission({required String text}) {
    return Text.rich(
      TextSpan(
        text: '${UiUtils.getTranslatedLabel(context, textSubmissionKey)}: ',
        children: replaceLink(text: text).map<InlineSpan>((data) {
          if (isLink(data)) {
            return TextSpan(
              text: data,
              recognizer: TapGestureRecognizer()
                ..onTap = () async {
                  if (await canLaunchUrl(Uri.parse(data))) {
                    await launchUrl(
                      Uri.parse(data),
                      mode: LaunchMode.externalApplication,
                    );
                  }
                },
              style: const TextStyle(
                decoration: TextDecoration.underline,
                fontWeight: FontWeight.normal,
              ),
            );
          } else {
            return TextSpan(
              text: data,
              style: const TextStyle(fontWeight: FontWeight.normal),
            );
          }
        }).toList(),
        style: TextStyle(
          color: Theme.of(context).colorScheme.secondary.withValues(alpha: 0.7),
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
      textAlign: TextAlign.start,
    );
  }

  //to display rejected, accepted,view and download
  Widget _buildStudentAssignmentActionButton({
    required String title,
    required double rightMargin,
    required double width,
    required Function onTap,
    required Color backgroundColor,
  }) {
    return InkWell(
      onTap: () {
        onTap();
      },
      borderRadius: BorderRadius.circular(5),
      child: Container(
        margin: EdgeInsets.only(right: rightMargin),
        alignment: Alignment.center,
        width: width,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(5),
          color: backgroundColor,
        ),
        padding: const EdgeInsets.symmetric(vertical: 5),
        child: Text(
          UiUtils.getTranslatedLabel(context, title),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: TextStyle(
            fontSize: 10.75,
            color: Theme.of(context).scaffoldBackgroundColor,
          ),
        ),
      ),
    );
  }

  Widget _buildStudentAssignmentDetailsContainer({
    required String assignmentFilterType,
    required bool isAssignmentFilterTypeAll,
    required ReviewAssignmentsSubmission reviewAssignment,
  }) {
    return Animate(
      effects: customItemFadeAppearanceEffects(),
      child: Container(
        margin: const EdgeInsets.only(bottom: 20),
        width: MediaQuery.sizeOf(context).width * 0.85,
        padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 15),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(15),
        ),
        child: LayoutBuilder(
          builder: (context, boxConstraints) {
            return Column(
              children: [
                Row(
                  children: [
                    Container(
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(15),
                        color: Theme.of(
                          context,
                        ).colorScheme.primary.withValues(alpha: 0.75),
                      ),
                      height: boxConstraints.maxWidth * 0.175,
                      width: boxConstraints.maxWidth * 0.175,
                      child: CustomUserProfileImageWidget(
                        profileUrl: reviewAssignment.student.user.image,
                        radius: BorderRadius.circular(15),
                      ),
                    ),
                    SizedBox(width: boxConstraints.maxWidth * 0.05),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.end,
                          children: [
                            SizedBox(
                              width: boxConstraints.maxWidth * 0.5,
                              child: Text(
                                '${reviewAssignment.student.user.firstName}${reviewAssignment.student.user.lastName}',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  color: Theme.of(
                                    context,
                                  ).colorScheme.secondary,
                                  fontWeight: FontWeight.w500,
                                  fontSize: 13,
                                ),
                                textAlign: TextAlign.start,
                              ),
                            ),
                            if (isAssignmentFilterTypeAll == true)
                              reviewAssignment.status == 0
                                  ? Container()
                                  : Container(
                                      alignment: Alignment.center,
                                      width: boxConstraints.maxWidth * 0.27,
                                      decoration: BoxDecoration(
                                        color:
                                            reviewAssignment.status == 1 ||
                                                reviewAssignment.status == 3
                                            ? Theme.of(
                                                context,
                                              ).colorScheme.onPrimary
                                            : Theme.of(
                                                context,
                                              ).colorScheme.error,
                                        borderRadius: BorderRadius.circular(
                                          2.5,
                                        ),
                                      ),
                                      padding: const EdgeInsets.symmetric(
                                        vertical: 2,
                                      ),
                                      child: Text(
                                        UiUtils.getTranslatedLabel(
                                          context,
                                          reviewAssignment.status == 1
                                              ? 'accepted'
                                              : reviewAssignment.status == 3
                                              ? reSubmittedKey
                                              : 'rejected',
                                        ), //
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: TextStyle(
                                          fontSize: 10.75,
                                          color: Theme.of(
                                            context,
                                          ).scaffoldBackgroundColor,
                                        ),
                                      ),
                                    )
                            else
                              const SizedBox(),
                          ],
                        ),
                        const SizedBox(height: 2.5),
                        Text(
                          'Submitted on ${reviewAssignment.assignment.dueDate}',
                          style: TextStyle(
                            color: Theme.of(
                              context,
                            ).colorScheme.secondary.withValues(alpha: 0.7),
                            fontWeight: FontWeight.w400,
                            fontSize: 11,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                if (assignmentFilterType == rejectedKey)
                  Container(
                    alignment: AlignmentDirectional.centerStart,
                    margin: EdgeInsets.only(
                      left: boxConstraints.maxWidth * 0.225,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          reviewAssignment.feedback,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.secondary,
                            fontWeight: FontWeight.w600,
                            fontSize: 12,
                          ),
                          textAlign: TextAlign.start,
                        ),
                        const SizedBox(height: 5),
                        if (reviewAssignment.textSubmission.isNotEmpty) ...[
                          _buildTextSubmission(
                            text: reviewAssignment.textSubmission,
                          ),
                          const SizedBox(height: 5),
                        ],
                        if (reviewAssignment.file.isNotEmpty)
                          GestureDetector(
                            onTap: () {
                              UiUtils.showBottomSheet(
                                child: AttachmentBottomsheetContainer(
                                  fromAnnouncementsContainer: false,
                                  studyMaterials: reviewAssignment.file,
                                ),
                                context: context,
                              );
                            },
                            child: Text(
                              '${reviewAssignment.file.length} ${UiUtils.getTranslatedLabel(context, attachmentsKey)}',
                              style: const TextStyle(
                                color: assignmentViewButtonColor,
                              ),
                            ),
                          ),
                      ],
                    ),
                  )
                else
                  const SizedBox(),
                if (assignmentFilterType == submitKey ||
                    assignmentFilterType == reSubmittedKey)
                  Container(
                    alignment: AlignmentDirectional.centerStart,
                    margin: EdgeInsets.only(
                      left: boxConstraints.maxWidth * 0.225,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (reviewAssignment.textSubmission.isNotEmpty) ...[
                          _buildTextSubmission(
                            text: reviewAssignment.textSubmission,
                          ),
                          const SizedBox(height: 7),
                        ],
                        if (reviewAssignment.file.isNotEmpty)
                          GestureDetector(
                            onTap: () {
                              UiUtils.showBottomSheet(
                                child: AttachmentBottomsheetContainer(
                                  fromAnnouncementsContainer: false,
                                  studyMaterials: reviewAssignment.file,
                                ),
                                context: context,
                              );
                            },
                            child: Text(
                              '${reviewAssignment.file.length} ${UiUtils.getTranslatedLabel(context, attachmentsKey)}',
                              style: const TextStyle(
                                color: assignmentViewButtonColor,
                              ),
                            ),
                          ),
                      ],
                    ),
                  )
                else
                  Container(),
                if (assignmentFilterType == acceptedKey)
                  Container(
                    alignment: AlignmentDirectional.centerStart,
                    margin: EdgeInsets.only(
                      left: boxConstraints.maxWidth * 0.225,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (reviewAssignment.feedback.isNotEmpty)
                          Text(
                            reviewAssignment.feedback,
                            style: TextStyle(
                              color: Theme.of(context).colorScheme.secondary,
                              fontWeight: FontWeight.w600,
                              fontSize: 12,
                            ),
                            textAlign: TextAlign.start,
                          ),
                        if (reviewAssignment.assignment.points != 0 &&
                            reviewAssignment.assignment.points != -1)
                          const SizedBox(height: 7),
                        if (reviewAssignment.assignment.points != 0 &&
                            reviewAssignment.assignment.points != -1)
                          Text(
                            UiUtils.getTranslatedLabel(context, pointsKey),
                            style: TextStyle(
                              color: Theme.of(
                                context,
                              ).colorScheme.secondary.withValues(alpha: 0.7),
                              fontWeight: FontWeight.w500,
                              fontSize: 12,
                            ),
                            textAlign: TextAlign.start,
                          ),
                        if (reviewAssignment.assignment.points != 0 &&
                            reviewAssignment.assignment.points != -1)
                          Text(
                            '${reviewAssignment.points} / ${widget.assignment.points}',
                            style: TextStyle(
                              color: Theme.of(context).colorScheme.secondary,
                              fontWeight: FontWeight.w600,
                              fontSize: 12,
                            ),
                            textAlign: TextAlign.start,
                          ),
                        const SizedBox(height: 7),
                        if (reviewAssignment.textSubmission.isNotEmpty) ...[
                          _buildTextSubmission(
                            text: reviewAssignment.textSubmission,
                          ),
                          const SizedBox(height: 7),
                        ],
                        if (reviewAssignment.file.isNotEmpty)
                          GestureDetector(
                            onTap: () {
                              UiUtils.showBottomSheet(
                                child: AttachmentBottomsheetContainer(
                                  fromAnnouncementsContainer: false,
                                  studyMaterials: reviewAssignment.file,
                                ),
                                context: context,
                              );
                            },
                            child: Text(
                              '${reviewAssignment.file.length} ${UiUtils.getTranslatedLabel(context, attachmentsKey)}',
                              style: const TextStyle(
                                color: assignmentViewButtonColor,
                              ),
                            ),
                          ),
                      ],
                    ),
                  )
                else
                  const SizedBox(),
                const SizedBox(height: 10),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    if (assignmentFilterType == acceptedKey ||
                        assignmentFilterType == rejectedKey)
                      const SizedBox()
                    else
                      _buildStudentAssignmentActionButton(
                        rightMargin: boxConstraints.maxWidth * 0.05,
                        width: boxConstraints.maxWidth * 0.2,
                        title: UiUtils.getTranslatedLabel(context, acceptKey),
                        onTap: () {
                          openAcceptAssignmentBottomsheet(reviewAssignment);
                        },
                        backgroundColor: Theme.of(
                          context,
                        ).colorScheme.onPrimary,
                      ),
                    if (assignmentFilterType == acceptedKey ||
                        assignmentFilterType == rejectedKey)
                      const SizedBox()
                    else
                      _buildStudentAssignmentActionButton(
                        rightMargin: boxConstraints.maxWidth * 0.05,
                        width: boxConstraints.maxWidth * 0.2,
                        title: UiUtils.getTranslatedLabel(context, rejectKey),
                        onTap: () {
                          openRejectAssignmentBottomsheet(reviewAssignment);
                        },
                        backgroundColor: Theme.of(context).colorScheme.error,
                      ),
                  ],
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _buildAssignments(List<ReviewAssignmentsSubmission> reviewAssignment) {
    // all key  show All Assignment Reject ,Accept & submit
    //Status 1 - Accepted
    //Status 2 - Rejected
    //Status 3 - Submitted but not reject
    if (_currentlySelectedAssignmentFilter == allKey) {
      final List<ReviewAssignmentsSubmission> acceptAssignment =
          reviewAssignment.where((e) => e.status == 1).toList();
      final List<ReviewAssignmentsSubmission> rejectAssignment =
          reviewAssignment.where((e) => e.status == 2).toList();
      final List<ReviewAssignmentsSubmission> submitedAssignment =
          reviewAssignment.where((e) => e.status == 0).toList();
      final List<ReviewAssignmentsSubmission> resubmittedAssignment =
          reviewAssignment.where((e) => e.status == 3).toList();

      return Column(
        children: [
          ...resubmittedAssignment.map(
            (reviewAssignment) => _buildStudentAssignmentDetailsContainer(
              isAssignmentFilterTypeAll: true,
              assignmentFilterType: submitKey,
              reviewAssignment: reviewAssignment,
            ),
          ),
          ...submitedAssignment.map(
            (reviewAssignment) => _buildStudentAssignmentDetailsContainer(
              isAssignmentFilterTypeAll: true,
              assignmentFilterType: submitKey,
              reviewAssignment: reviewAssignment,
            ),
          ),
          ...acceptAssignment.map(
            (reviewAssignment) => _buildStudentAssignmentDetailsContainer(
              isAssignmentFilterTypeAll: true,
              reviewAssignment: reviewAssignment,
              assignmentFilterType: acceptedKey,
            ),
          ),
          ...rejectAssignment.map(
            (reviewAssignment) => _buildStudentAssignmentDetailsContainer(
              isAssignmentFilterTypeAll: true,
              assignmentFilterType: rejectedKey,
              reviewAssignment: reviewAssignment,
            ),
          ),
        ],
      );
    }
    if (_currentlySelectedAssignmentFilter == acceptedKey) {
      //status 1 is Accept Assignment
      final List<ReviewAssignmentsSubmission> acceptedAssignment =
          reviewAssignment.where((e) => e.status == 1).toList();
      //
      return Column(
        children: [
          ...acceptedAssignment.map(
            (e) => _buildStudentAssignmentDetailsContainer(
              isAssignmentFilterTypeAll: false,
              reviewAssignment: e,
              assignmentFilterType: acceptedKey,
            ),
          ),
        ],
      );
    }
    if (_currentlySelectedAssignmentFilter == rejectedKey) {
      //Status 2 is Reject Assigment
      final List<ReviewAssignmentsSubmission> rejectAssignment =
          reviewAssignment.where((e) => e.status == 2).toList();
      //
      return Column(
        children: [
          ...rejectAssignment.map(
            (e) => _buildStudentAssignmentDetailsContainer(
              isAssignmentFilterTypeAll: false,
              reviewAssignment: e,
              assignmentFilterType: rejectedKey,
            ),
          ),
        ],
      );
    }
    if (_currentlySelectedAssignmentFilter == submittedKey) {
      // Status 0 is show Assignment Which one is not Accepted of Rejected
      final List<ReviewAssignmentsSubmission> submitedAssignment =
          reviewAssignment.where((e) => e.status == 0).toList();

      //
      return Column(
        children: [
          ...submitedAssignment.map(
            (e) => _buildStudentAssignmentDetailsContainer(
              isAssignmentFilterTypeAll: false,
              reviewAssignment: e,
              assignmentFilterType: submitKey,
            ),
          ),
        ],
      );
    }

    if (_currentlySelectedAssignmentFilter == reSubmittedKey) {
      //Status 2 is Reject Assigment
      final List<ReviewAssignmentsSubmission> resubmittedAssignment =
          reviewAssignment.where((e) => e.status == 3).toList();
      //
      return Column(
        children: [
          ...resubmittedAssignment.map(
            (e) => _buildStudentAssignmentDetailsContainer(
              isAssignmentFilterTypeAll: false,
              reviewAssignment: e,
              assignmentFilterType: reSubmittedKey,
            ),
          ),
        ],
      );
    }

    return const Column();
  }

  Widget _buildAssignmentListWithFiltersContainer(
    List<ReviewAssignmentsSubmission> reviewAssignment,
  ) {
    return ListView(
      padding: EdgeInsets.only(
        top: UiUtils.getScrollViewTopPadding(
          context: context,
          appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
        ),
      ),
      children: [
        _buildAssignmentSubmissionFilters(reviewAssignment),
        const SizedBox(height: 20),
        _buildAssignments(reviewAssignment),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          CustomRefreshIndicator(
            displacement: UiUtils.getScrollViewTopPadding(
              context: context,
              appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
            ),
            onRefreshCallback: () {
              fetchReviewAssignment();
            },
            child: BlocBuilder<ReviewAssignmentCubit, ReviewAssignmentState>(
              bloc: context.read<ReviewAssignmentCubit>(),
              builder: (context, state) {
                if (state is ReviewAssignmentSuccess) {
                  return _buildAssignmentListWithFiltersContainer(
                    state.reviewAssignment,
                  );
                }
                if (state is ReviewAssignmentFailure) {
                  return Center(
                    child: ErrorContainer(
                      errorMessageCode: state.errorMessage,
                      onTapRetry: () {
                        fetchReviewAssignment();
                      },
                    ),
                  );
                }
                return ListView(
                  padding: EdgeInsets.only(
                    top: UiUtils.getScrollViewTopPadding(
                      context: context,
                      appBarHeightPercentage:
                          UiUtils.appBarSmallerHeightPercentage,
                    ),
                  ),
                  children: [
                    LayoutBuilder(
                      builder: (context, boxConstraints) {
                        return Column(
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                              children: [
                                ...List.generate(
                                  4,
                                  (index) => ShimmerLoadingContainer(
                                    child: CustomShimmerContainer(
                                      height: 35,
                                      borderRadius: 10,
                                      width: boxConstraints.maxWidth * 0.20,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        );
                      },
                    ),
                    ...List.generate(
                      10,
                      (index) => Padding(
                        padding: const EdgeInsets.only(left: 20),
                        child: _buildInformationShimmerLoadingContainer(),
                      ),
                    ),
                  ],
                );
              },
            ),
          ),
          _buildAppbar(),
        ],
      ),
    );
  }
}
