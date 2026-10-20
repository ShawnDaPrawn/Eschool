import 'package:eschool/ui/widgets/customShimmerContainer.dart';
import 'package:eschool/ui/widgets/shimmerLoadingContainer.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class TimetableShimmerLoadingContainer extends StatelessWidget {
  const TimetableShimmerLoadingContainer({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 25),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      width: MediaQuery.sizeOf(context).width * 0.85,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Row(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const ShimmerLoadingContainer(
                child: CustomShimmerContainer(
                  borderRadius: 100,
                  width: 50,
                  height: 50,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const SizedBox(height: 10),
                    ShimmerLoadingContainer(
                      child: CustomShimmerContainer(
                        borderRadius: 3,
                        width: boxConstraints.maxWidth * 0.4,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        ShimmerLoadingContainer(
                          child: CustomShimmerContainer(
                            borderRadius: 3,
                            height:
                                UiUtils.shimmerLoadingContainerDefaultHeight -
                                2,
                            width: boxConstraints.maxWidth * 0.4,
                          ),
                        ),
                        const SizedBox(width: 10),
                        ShimmerLoadingContainer(
                          child: CustomShimmerContainer(
                            borderRadius: 3,
                            height:
                                UiUtils.shimmerLoadingContainerDefaultHeight -
                                2,
                            width: boxConstraints.maxWidth * 0.35,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
