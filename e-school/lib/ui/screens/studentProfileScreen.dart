import 'package:eschool/app/routes.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/widgets/customAppbar.dart';
import 'package:eschool/ui/widgets/dynamicFieldListContainer.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:eschool/cubits/downloadIdCardCubit.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';

class StudentProfileScreen extends StatefulWidget {
  const StudentProfileScreen({required this.studentDetails, super.key});
  final Student studentDetails;

  @override
  State<StudentProfileScreen> createState() => _StudentProfileScreenState();

  static Route route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (_) => StudentProfileScreen(
        studentDetails: routeSettings.arguments! as Student,
      ),
    );
  }
}

class _StudentProfileScreenState extends State<StudentProfileScreen> {
  Widget _buildProfileDetailsTile({
    required String label,
    required String value,
    required String iconUrl,
    Color? iconColor,
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
              boxShadow: [
                BoxShadow(
                  color: Theme.of(
                    context,
                  ).colorScheme.shadow.withValues(alpha: 0.1),
                  offset: const Offset(0, 10),
                  blurRadius: 16,
                ),
              ],
              color: Theme.of(context).colorScheme.primary,
              borderRadius: BorderRadius.circular(15),
            ),
            child: SvgPicture.asset(
              iconUrl,
              colorFilter: iconColor == null
                  ? null
                  : ColorFilter.mode(iconColor, BlendMode.srcIn),
            ),
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
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                      fontWeight: FontWeight.w400,
                      fontSize: 12,
                    ),
                  ),
                  Text(
                    value,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                      fontWeight: FontWeight.w500,
                      fontSize: 14,
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
    return Align(
      alignment: Alignment.topCenter,
      child: CustomAppBar(
        title: UiUtils.getTranslatedLabel(context, profileKey),
      ),
    );
  }

  Widget _buildTopProfileCard() {
    return Align(
      alignment: Alignment.topCenter,
      child: Container(
        margin: EdgeInsets.only(
          left: MediaQuery.sizeOf(context).width * 0.075,
          right: MediaQuery.sizeOf(context).width * 0.075,
        ),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surfaceContainerLow,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Theme.of(
                context,
              ).colorScheme.shadow.withValues(alpha: 0.2),
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
              child: CustomUserProfileImageWidget(
                profileUrl: widget.studentDetails.image,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              widget.studentDetails.getFullName(),
              style: TextStyle(
                fontWeight: FontWeight.w600,
                color: Theme.of(context).colorScheme.onSurface,
                fontSize: 16,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              '${UiUtils.getTranslatedLabel(context, grNumberKey)} : ${widget.studentDetails.admissionNo}',
              style: TextStyle(
                color: Theme.of(context).colorScheme.primary,
                fontSize: 12,
                fontWeight: FontWeight.w500,
              ),
            ),
            const SizedBox(height: 8),
            Divider(
              color: Theme.of(
                context,
              ).colorScheme.onSurfaceVariant.withValues(alpha: 0.1),
            ),
            const SizedBox(height: 8),
            _buildFourItemsGrid(),
            const SizedBox(height: 16),
            _buildDownloadIdCardButton(),
          ],
        ),
      ),
    );
  }

  Widget _buildProfileDetailsContainer() {
    return Align(
      alignment: Alignment.topCenter,
      child: SingleChildScrollView(
        padding: EdgeInsets.only(
          top: UiUtils.getScrollViewTopPadding(
            context: context,
            appBarHeightPercentage: UiUtils.appBarSmallerHeightPercentage,
          ),
        ),
        child: Column(
          children: [
            _buildTopProfileCard(),
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
                      UiUtils.getTranslatedLabel(context, personalDetailsKey),
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                        fontSize: 15,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                  if (widget.studentDetails.shift != null &&
                      widget.studentDetails.shift!.title.trim().isNotEmpty)
                    _buildProfileDetailsTile(
                      label: UiUtils.getTranslatedLabel(context, shiftKey),
                      value: UiUtils.formatEmptyValue(
                        "${widget.studentDetails.shift!.title}${widget.studentDetails.shift!.startToEndTime == null ? '' : ' (${widget.studentDetails.shift!.startToEndTime})'}",
                      ),
                      iconUrl: Assets.userProShiftIcon,
                    ),
                  _buildProfileDetailsTile(
                    label: UiUtils.getTranslatedLabel(context, dateOfBirthKey),
                    value: UiUtils.formatEmptyValue(
                      DateTime.tryParse(widget.studentDetails.dob) == null
                          ? widget.studentDetails.dob
                          : UiUtils.formatDate(
                              DateTime.tryParse(widget.studentDetails.dob)!,
                            ),
                    ),
                    iconUrl: Assets.userProDobIcon,
                  ),
                  _buildProfileDetailsTile(
                    label: UiUtils.getTranslatedLabel(
                      context,
                      currentAddressKey,
                    ),
                    value: UiUtils.formatEmptyValue(
                      widget.studentDetails.currentAddress,
                    ),
                    iconUrl: Assets.userProAddressIcon,
                  ),
                  _buildProfileDetailsTile(
                    label: UiUtils.getTranslatedLabel(
                      context,
                      permanentAddressKey,
                    ),
                    value: UiUtils.formatEmptyValue(
                      widget.studentDetails.permanentAddress,
                    ),
                    iconUrl: Assets.userProAddressIcon,
                  ),
                  _buildProfileDetailsTile(
                    label: UiUtils.getTranslatedLabel(context, bloodGroupKey),
                    value: UiUtils.formatEmptyValue(
                      widget.studentDetails.bloodGroup,
                    ),
                    iconUrl: Assets.bloodIcon,
                  ),
                  _buildProfileDetailsTile(
                    label: UiUtils.getTranslatedLabel(context, weightKey),
                    value: UiUtils.formatEmptyValue(
                      widget.studentDetails.weight,
                    ),
                    iconUrl: Assets.weightIcon,
                  ),
                  _buildProfileDetailsTile(
                    label: UiUtils.getTranslatedLabel(context, heightKey),
                    value: UiUtils.formatEmptyValue(
                      widget.studentDetails.height,
                    ),
                    iconUrl: Assets.heightIcon,
                  ),
                ],
              ),
            ),
            //dynamic field data
            if (widget.studentDetails.dynamicFields != null)
              Padding(
                padding: EdgeInsets.symmetric(
                  horizontal: MediaQuery.sizeOf(context).width * 0.075,
                ),
                child: Divider(
                  color: Theme.of(
                    context,
                  ).colorScheme.onSurfaceVariant.withValues(alpha: 0.75),
                ),
              ),
            if (widget.studentDetails.dynamicFields?.isNotEmpty ?? false) ...[
              const SizedBox(height: 10),
              Padding(
                padding: EdgeInsets.symmetric(
                  horizontal: MediaQuery.sizeOf(context).width * 0.075,
                ),
                child: DynamicFieldListContainer(
                  dynamicFields: widget.studentDetails.dynamicFields!,
                ),
              ),
            ],
            SizedBox(height: MediaQuery.sizeOf(context).height * 0.1),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoCard({required String title, required String subtitle}) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: Theme.of(
          context,
        ).colorScheme.onSurfaceVariant.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              title,
              style: TextStyle(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
                fontWeight: FontWeight.w600,
                fontSize: 14,
              ),
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 4),
            Text(
              subtitle,
              style: TextStyle(
                color: Theme.of(
                  context,
                ).colorScheme.onSurfaceVariant.withValues(alpha: 0.5),
                fontWeight: FontWeight.w400,
                fontSize: 12,
              ),
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFourItemsGrid() {
    final String sessionYear = context
        .read<AppConfigurationCubit>()
        .getAppConfiguration()
        .sessionYear
        .name;
    final String medium = widget.studentDetails.mediumName;

    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _buildInfoCard(
                title: UiUtils.formatEmptyValue(
                  widget.studentDetails.classSectionNameWithSemester(
                    context: context,
                  ),
                ),
                subtitle: UiUtils.getTranslatedLabel(context, classKey),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildInfoCard(
                title: widget.studentDetails.rollNumber.toString(),
                subtitle: UiUtils.getTranslatedLabel(context, rollNoKey),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _buildInfoCard(
                title: sessionYear,
                subtitle: UiUtils.getTranslatedLabel(context, sessionYearKey),
              ),
            ),
            if (medium.isNotEmpty) ...[
              const SizedBox(width: 15),
              Expanded(
                child: _buildInfoCard(
                  title: medium,
                  subtitle: UiUtils.getTranslatedLabel(context, mediumKey),
                ),
              ),
            ],
          ],
        ),
      ],
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
              arguments: PdfFileViewArguments(
                studyMaterial: StudyMaterial.fromURL(state.filePath),
                isFile: true,
              ),
            );
          } else if (state is DownloadIdCardFailure) {
            UiUtils.showCustomSnackBar(
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
                childId: context.read<AuthCubit>().isParent()
                    ? widget.studentDetails.id.toString()
                    : null,
                childName: widget.studentDetails.getFullName(),
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
    return Scaffold(
      body: Stack(children: [_buildProfileDetailsContainer(), _buildAppBar()]),
    );
  }
}
