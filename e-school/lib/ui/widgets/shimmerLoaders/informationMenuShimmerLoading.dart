import 'package:eschool/ui/widgets/customShimmerContainer.dart';
import 'package:eschool/ui/widgets/shimmerLoadingContainer.dart';
import 'package:flutter/cupertino.dart';

class InformationMenuShimmerContainer extends StatelessWidget {
  const InformationMenuShimmerContainer({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        bottom: 15,
        left: MediaQuery.sizeOf(context).width * 0.08,
        right: MediaQuery.sizeOf(context).width * 0.08,
      ),
      height: 80,
      child: LayoutBuilder(
        builder: (context, boxConstraints) {
          return Row(
            children: [
              ShimmerLoadingContainer(
                child: CustomShimmerContainer(
                  height: 60,
                  width: boxConstraints.maxWidth * 0.225,
                ),
              ),
              SizedBox(width: boxConstraints.maxWidth * 0.05),
              ShimmerLoadingContainer(
                child: CustomShimmerContainer(
                  width: boxConstraints.maxWidth * 0.475,
                ),
              ),
              const Spacer(),
              ShimmerLoadingContainer(
                child: CustomShimmerContainer(
                  borderRadius: boxConstraints.maxWidth * 0.1,
                  height: boxConstraints.maxWidth * 0.09,
                  width: boxConstraints.maxWidth * 0.09,
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
