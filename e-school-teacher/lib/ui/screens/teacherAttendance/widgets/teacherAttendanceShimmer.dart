import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

class TeacherAttendanceShimmer extends StatelessWidget {
  const TeacherAttendanceShimmer({super.key});

  @override
  Widget build(BuildContext context) {
    return ShimmerLoadingContainer(
      child: Column(
        children: [
          // Calendar shimmer
          CustomShimmerContainer(
            height: MediaQuery.sizeOf(context).height * 0.4,
            width: double.maxFinite,
            customBorderRadius: BorderRadius.only(
              bottomLeft: Radius.circular(12),
              bottomRight: Radius.circular(12),
            ),
          ),
          const SizedBox(height: 20),
          // Total Attendance shimmer
          const CustomShimmerContainer(height: 100, width: double.maxFinite),
          const SizedBox(height: 20),
          // 4 boxes shimmer
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            padding: EdgeInsets.zero,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              crossAxisSpacing: 15,
              mainAxisSpacing: 15,
              childAspectRatio: 1.2,
            ),
            itemCount: 4,
            itemBuilder: (context, index) {
              return const CustomShimmerContainer(height: 100);
            },
          ),
        ],
      ),
    );
  }
}
