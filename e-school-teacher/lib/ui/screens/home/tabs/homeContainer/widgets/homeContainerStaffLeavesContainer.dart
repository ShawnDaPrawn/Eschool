import 'package:eschool_teacher/data/models/staffLeave.dart';
import 'package:eschool_teacher/ui/widgets/customImageWidget.dart';
import 'package:eschool_teacher/ui/widgets/noDataContainer.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class HomeContainerStaffLeavesContainer extends StatefulWidget {
  const HomeContainerStaffLeavesContainer({
    required this.today, required this.tomorrow, required this.upcoming, super.key,
  });
  final List<StaffLeave> today;
  final List<StaffLeave> tomorrow;
  final List<StaffLeave> upcoming;

  @override
  State<HomeContainerStaffLeavesContainer> createState() =>
      _HomeContainerStaffLeavesContainerState();
}

class _HomeContainerStaffLeavesContainerState
    extends State<HomeContainerStaffLeavesContainer>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController =
      TabController(length: 3, vsync: this);

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Widget _buildStaffLeaveContainer(StaffLeave leave) {
    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(8),
        border: Border.all(
          color:
              Theme.of(context).colorScheme.secondary.withValues(alpha: 0.25),
        ),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Container(
                height: 50,
                width: 50,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(2),
                ),
                clipBehavior: Clip.antiAlias,
                child: CustomImageWidget(
                  imagePath: leave.image,
                ),
              ),
              const SizedBox(
                width: 8,
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(
                      leave.userName,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.secondary,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(
                      height: 4,
                    ),
                    Text(
                      '${UiUtils.getTranslatedLabel(context, roleKey)} : ${leave.role}',
                      textAlign: TextAlign.start,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.onSurface,
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          Divider(
            color:
                Theme.of(context).colorScheme.secondary.withValues(alpha: 0.25),
          ),
          Row(
            children: [
              Expanded(
                child: Text.rich(
                  TextSpan(
                    children: [
                      WidgetSpan(
                        alignment: PlaceholderAlignment.middle,
                        child: Icon(
                          Icons.calendar_month_outlined,
                          size: 18,
                          color: Theme.of(context).colorScheme.onSurface,
                        ),
                      ),
                      TextSpan(
                        text: ' ${leave.date}',
                      ),
                    ],
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.onSurface,
                    fontSize: 14,
                    fontWeight: FontWeight.w400,
                  ),
                ),
              ),
              const SizedBox(
                width: 8,
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 8,
                ),
                decoration: BoxDecoration(
                  color: Theme.of(context)
                      .colorScheme
                      .primary
                      .withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Text(
                  leave.type,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.primary,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStaffLeaveList({required List<StaffLeave> leaveList}) {
    return ListView.separated(
      padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
      itemBuilder: (context, index) {
        return _buildStaffLeaveContainer(leaveList[index]);
      },
      separatorBuilder: (context, index) => const SizedBox(
        height: 8,
      ),
      itemCount: leaveList.length,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
      ),
      height: 400,
      child: Column(
        children: [
          TabBar(
            controller: _tabController,
            unselectedLabelColor: Theme.of(context).colorScheme.secondary,
            unselectedLabelStyle: const TextStyle(),
            labelColor: Theme.of(context).scaffoldBackgroundColor,
            labelStyle: const TextStyle(fontWeight: FontWeight.bold),
            indicatorSize: TabBarIndicatorSize.tab,
            indicator: BoxDecoration(
              borderRadius: BorderRadius.circular(4),
              color: Theme.of(context).colorScheme.onPrimary,
            ),
            indicatorWeight: 0,
            dividerHeight: 0,
            dividerColor: Colors.transparent,
            tabs: [
              Tab(text: UiUtils.getTranslatedLabel(context, todayKey)),
              Tab(text: UiUtils.getTranslatedLabel(context, tomorrowKey)),
              Tab(text: UiUtils.getTranslatedLabel(context, upComingKey)),
            ],
          ),
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                ///[Today]
                if (widget.today.isEmpty)
                  const NoDataContainer(
                    titleKey: noStaffOnLeaveKey,
                    imageSize: 200,
                  )
                else
                  _buildStaffLeaveList(leaveList: widget.today),

                ///[Tomorrow]
                if (widget.tomorrow.isEmpty)
                  const NoDataContainer(
                    titleKey: noStaffOnLeaveKey,
                    imageSize: 200,
                  )
                else
                  _buildStaffLeaveList(leaveList: widget.tomorrow),

                ///[Upcoming]
                if (widget.upcoming.isEmpty)
                  const NoDataContainer(
                    titleKey: noStaffOnLeaveKey,
                    imageSize: 200,
                  )
                else
                  _buildStaffLeaveList(leaveList: widget.upcoming),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
