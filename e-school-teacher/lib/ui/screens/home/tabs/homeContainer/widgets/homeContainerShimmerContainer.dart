import 'dart:math';
import 'package:eschool_teacher/ui/widgets/albumGridItemLoadingContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/cupertino.dart';

class HomeContainerShimmerContainer extends StatelessWidget {
  const HomeContainerShimmerContainer({super.key});

  Widget _titleShimmer(BuildContext context) {
    final randomWidthPercentage = Random().nextDouble() * 0.7;
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: MediaQuery.sizeOf(context).width * 0.075,
      ),
      child: CustomShimmerContainer(
        height: 30,
        borderRadius: 8,
        width:
            MediaQuery.sizeOf(context).width *
            (randomWidthPercentage < 0.24 ? 0.24 : randomWidthPercentage),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return ShimmerLoadingContainer(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          _titleShimmer(context),
          const SizedBox(height: 12),
          GridView.count(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisCount: 2,
            padding: EdgeInsets.symmetric(
              horizontal: MediaQuery.sizeOf(context).width * 0.075,
            ),
            mainAxisSpacing: 12,
            crossAxisSpacing: 12,
            childAspectRatio: 1.3,
            children: List.generate(
              2,
              (index) => const CustomShimmerContainer(
                height: double.maxFinite,
                borderRadius: 8,
                width: double.maxFinite,
              ),
            ),
          ),
          const SizedBox(height: 24),
          _titleShimmer(context),
          const SizedBox(height: 12),
          SizedBox(
            height: 90,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: EdgeInsets.symmetric(
                horizontal: MediaQuery.sizeOf(context).width * 0.075,
              ),
              itemBuilder: (context, index) {
                return const CustomShimmerContainer(
                  height: 90,
                  borderRadius: 8,
                  width: 180,
                );
              },
              separatorBuilder: (context, index) {
                return const SizedBox(width: 12);
              },
              itemCount: 5,
            ),
          ),
          const SizedBox(height: 24),
          _titleShimmer(context),
          const SizedBox(height: 12),
          GridView.count(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisCount: 3,
            padding: EdgeInsets.symmetric(
              horizontal: MediaQuery.sizeOf(context).width * 0.075,
            ),
            mainAxisSpacing: 8,
            crossAxisSpacing: 8,
            children: List.generate(
              9,
              (index) => const CustomShimmerContainer(
                height: double.maxFinite,
                borderRadius: 4,
                width: double.maxFinite,
              ),
            ),
          ),
          const SizedBox(height: 24),
          _titleShimmer(context),
          const SizedBox(height: 12),
          ListView.separated(
            physics: const NeverScrollableScrollPhysics(),
            shrinkWrap: true,
            padding: EdgeInsets.symmetric(
              horizontal: MediaQuery.sizeOf(context).width * 0.075,
            ),
            itemBuilder: (context, index) {
              return const CustomShimmerContainer(
                height: 130,
                borderRadius: 8,
                width: double.maxFinite,
              );
            },
            separatorBuilder: (context, index) {
              return const SizedBox(height: 12);
            },
            itemCount: 3,
          ),
          const SizedBox(height: 12),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: EdgeInsets.symmetric(
              horizontal: MediaQuery.sizeOf(context).width * 0.075,
            ),
            child: Row(
              spacing: 16,
              children: List.generate(
                3,
                (index) => index,
              ).map((e) => const AlbumGridItemLoadingContainer()).toList(),
            ),
          ),
        ],
      ),
    );
  }
}
