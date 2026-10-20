import 'package:eschool/ui/widgets/customShimmerContainer.dart';
import 'package:eschool/ui/widgets/shimmerLoadingContainer.dart';
import 'package:flutter/material.dart';

class AlbumGridItemLoadingContainer extends StatelessWidget {
  const AlbumGridItemLoadingContainer({super.key});

  @override
  Widget build(BuildContext context) {
    return ShimmerLoadingContainer(
      child: CustomShimmerContainer(
        borderRadius: 12,
        width: (MediaQuery.of(context).size.width * .5) - 16,
        height: (MediaQuery.of(context).size.width * .5) - 16,
      ),
    );
  }
}
