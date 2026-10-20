import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/authCubit.dart';
import 'package:eschool_teacher/cubits/downloadIdCardCubit.dart';
import 'package:eschool_teacher/data/models/studyMaterial.dart';
import 'package:eschool_teacher/data/models/teacher.dart';
import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:eschool_teacher/ui/widgets/customCircularProgressIndicator.dart';
import 'package:eschool_teacher/ui/widgets/customUserProfileImageWidget.dart';
import 'package:eschool_teacher/ui/widgets/dynamicFieldListContainer.dart';
import 'package:eschool_teacher/ui/widgets/screenTopBackgroundContainer.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/flutter_svg.dart';

class ProfileContainer extends StatefulWidget {
  const ProfileContainer({super.key});

  @override
  State<ProfileContainer> createState() => _ProfileContainerState();
}

class _ProfileContainerState extends State<ProfileContainer> {
  Widget _buildProfileDetailsTile({
    required String label,
    required String value,
    required String iconUrl,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(12.5),
            width: 50,
            height: 50,
            decoration: BoxDecoration(
              boxShadow: const [
                BoxShadow(
                  color: Color(0x1a212121),
                  offset: Offset(0, 10),
                  blurRadius: 16,
                ),
              ],
              color: Theme.of(context).colorScheme.primary,
              borderRadius: BorderRadius.circular(15),
            ),
            child: SvgPicture.asset(iconUrl),
          ),
          SizedBox(width: MediaQuery.sizeOf(context).width * 0.05),
          Flexible(
            child: Padding(
              padding: const EdgeInsets.only(top: 3),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface,
                      fontWeight: FontWeight.w400,
                      fontSize: 12,
                    ),
                  ),
                  Text(
                    value.trim().isEmpty ? '-' : value,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.secondary,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAppBar() {
    return ScreenTopBackgroundContainer(
      heightPercentage: UiUtils.appBarSmallerHeightPercentage,
      padding: EdgeInsets.zero,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          Align(
            child: Text(
              UiUtils.getTranslatedLabel(context, profileKey),
              style: TextStyle(
                color: Theme.of(context).scaffoldBackgroundColor,
                fontSize: UiUtils.screenTitleFontSize,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTopProfileCard({required Teacher teacher}) {
    return Align(
      alignment: Alignment.topCenter,
      child: Container(
        margin: EdgeInsets.only(
          left: MediaQuery.sizeOf(context).width * 0.075,
          right: MediaQuery.sizeOf(context).width * 0.075,
        ),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Theme.of(
                context,
              ).colorScheme.secondary.withValues(alpha: 0.2),
              blurRadius: 12,
            ),
          ],
        ),
        padding: EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 56,
              height: 56,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Theme.of(context).colorScheme.primary,
              ),
              clipBehavior: Clip.antiAlias,
              child: CustomUserProfileImageWidget(profileUrl: teacher.image),
            ),
            const SizedBox(height: 8),
            Text(
              teacher.getFullName(),
              style: TextStyle(
                fontWeight: FontWeight.w600,
                color: Theme.of(context).colorScheme.secondary,
                fontSize: 16,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              teacher.qualification,
              style: TextStyle(
                fontWeight: FontWeight.w600,
                color: Theme.of(context).colorScheme.primary,
                fontSize: 12,
              ),
            ),
            const SizedBox(height: 8),
            Divider(
              color: Theme.of(
                context,
              ).colorScheme.onSurface.withValues(alpha: 0.1),
            ),
            const SizedBox(height: 8),
            _buildDownloadIdCardButton(),
          ],
        ),
      ),
    );
  }

  Widget _buildDownloadIdCardButton() {
    return BlocProvider(
      create: (context) => DownloadIdCardCubit(SystemRepository()),
      child: BlocConsumer<DownloadIdCardCubit, DownloadIdCardState>(
        listener: (context, state) {
          if (state is DownloadIdCardSuccess) {
            Navigator.of(context).pushNamed(
              Routes.pdfFileView,
              arguments: {
                'studyMaterial': StudyMaterial.fromURL(state.filePath),
                'isFile': true,
              },
            );
          } else if (state is DownloadIdCardFailure) {
            UiUtils.showBottomToastOverlay(
              context: context,
              errorMessage: UiUtils.getErrorMessageFromErrorCode(
                context,
                state.exception,
              ),
              backgroundColor: Theme.of(context).colorScheme.error,
            );
          }
        },
        builder: (context, state) {
          return InkWell(
            onTap: () {
              context.read<DownloadIdCardCubit>().downloadIdCard(
                teacherName: context
                    .read<AuthCubit>()
                    .getTeacherDetails()
                    .getFullName(),
              );
            },
            borderRadius: BorderRadius.circular(8),
            child: Container(
              height: 24,
              alignment: Alignment.center,
              child: state is DownloadIdCardInProgress
                  ? SizedBox(
                      height: 20,
                      width: 20,
                      child: CustomCircularProgressIndicator(
                        indicatorColor: Theme.of(context).colorScheme.primary,
                      ),
                    )
                  : Row(
                      mainAxisSize: MainAxisSize.min,
                      spacing: 8,
                      children: [
                        Text(
                          UiUtils.getTranslatedLabel(context, viewIDCardKey),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.primary,
                            fontWeight: FontWeight.w600,
                            fontSize: 14,
                          ),
                        ),
                        Icon(
                          Icons.arrow_forward_ios,
                          size: 14,
                          color: Theme.of(context).colorScheme.primary,
                        ),
                      ],
                    ),
            ),
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final teacher = context.read<AuthCubit>().getTeacherDetails();
    return Scaffold(
      body: Stack(
        children: [
          Align(
            alignment: Alignment.topCenter,
            child: SingleChildScrollView(
              padding: EdgeInsets.only(
                bottom: UiUtils.getScrollViewBottomPadding(context),
                top: UiUtils.getScrollViewTopPadding(
                  context: context,
                  appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
                ),
              ),
              child: Column(
                children: [
                  _buildTopProfileCard(teacher: teacher),
                  const SizedBox(height: 24),
                  Padding(
                    padding: EdgeInsets.symmetric(
                      horizontal: MediaQuery.sizeOf(context).width * 0.075,
                    ),
                    child: Column(
                      children: [
                        Align(
                          alignment: AlignmentDirectional.centerStart,
                          child: Text(
                            UiUtils.getTranslatedLabel(
                              context,
                              personalDetailsKey,
                            ),
                            style: TextStyle(
                              color: Theme.of(context).colorScheme.secondary,
                              fontSize: 15,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        _buildProfileDetailsTile(
                          label: UiUtils.getTranslatedLabel(context, emailKey),
                          value: teacher.email,
                          iconUrl: Assets.userProIcon,
                        ),
                        _buildProfileDetailsTile(
                          label: UiUtils.getTranslatedLabel(
                            context,
                            phoneNumberKey,
                          ),
                          value: teacher.mobile,
                          iconUrl: Assets.phoneCall,
                        ),
                        _buildProfileDetailsTile(
                          label: UiUtils.getTranslatedLabel(
                            context,
                            dateOfBirthKey,
                          ),
                          value: DateTime.tryParse(teacher.dob) == null
                              ? teacher.dob
                              : UiUtils.formatDate(
                                  DateTime.tryParse(teacher.dob)!,
                                ),
                          iconUrl: Assets.userProDobIcon,
                        ),
                        _buildProfileDetailsTile(
                          label: UiUtils.getTranslatedLabel(context, genderKey),
                          value: teacher.gender,
                          iconUrl: Assets.gender,
                        ),
                        _buildProfileDetailsTile(
                          label: UiUtils.getTranslatedLabel(
                            context,
                            qualificationKey,
                          ),
                          value: teacher.qualification,
                          iconUrl: Assets.qualification,
                        ),
                        _buildProfileDetailsTile(
                          label: UiUtils.getTranslatedLabel(
                            context,
                            currentAddressKey,
                          ),
                          value: teacher.currentAddress,
                          iconUrl: Assets.userProAddressIcon,
                        ),
                        _buildProfileDetailsTile(
                          label: UiUtils.getTranslatedLabel(
                            context,
                            permanentAddressKey,
                          ),
                          value: teacher.permanentAddress,
                          iconUrl: Assets.userProAddressIcon,
                        ),
                        if (teacher.dynamicFields?.isNotEmpty ?? false) ...[
                          Divider(
                            color: Theme.of(
                              context,
                            ).colorScheme.onSurface.withValues(alpha: 0.75),
                          ),
                          const SizedBox(height: 10),
                          DynamicFieldListContainer(
                            dynamicFields: teacher.dynamicFields!,
                          ),
                        ],
                        const SizedBox(height: 7.5),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),

          Align(alignment: Alignment.topCenter, child: _buildAppBar()),
        ],
      ),
    );
  }
}
