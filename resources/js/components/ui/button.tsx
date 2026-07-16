import * as React from "react"
import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"

import { cn } from "@/lib/utils"

const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl text-sm font-semibold transition-[color,box-shadow,transform] disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/40 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "bg-[image:var(--gradient-brand)] text-primary-foreground shadow-[0_12px_28px_-14px_color-mix(in_oklab,var(--color-primary)_70%,transparent)] hover:brightness-[1.03] hover:-translate-y-px",
        destructive:
          "bg-destructive text-white shadow-[0_10px_24px_-14px_color-mix(in_oklab,var(--color-destructive)_60%,transparent)] hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40",
        outline:
          "border border-input/90 bg-background/90 text-foreground shadow-[0_1px_2px_rgb(27_31_59_/_0.04)] hover:border-primary/30 hover:bg-accent/85 hover:text-accent-foreground dark:shadow-[0_12px_24px_-22px_rgb(0_0_0_/_0.7)]",
        secondary:
          "bg-secondary text-secondary-foreground shadow-[0_1px_2px_rgb(27_31_59_/_0.04)] hover:bg-secondary/88 dark:shadow-[0_12px_24px_-22px_rgb(0_0_0_/_0.55)]",
        ghost: "text-muted-foreground hover:bg-accent/85 hover:text-foreground",
        link: "text-primary underline-offset-4 hover:underline",
      },
      size: {
        default: "h-10 px-4 py-2 has-[>svg]:px-3.5",
        sm: "h-9 rounded-xl px-3.5 has-[>svg]:px-3",
        lg: "h-11 rounded-xl px-6 has-[>svg]:px-4",
        icon: "size-10 rounded-2xl",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

function Button({
  className,
  variant,
  size,
  asChild = false,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean
  }) {
  const Comp = asChild ? Slot : "button"

  return (
    <Comp
      data-slot="button"
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
    />
  )
}

export { Button, buttonVariants }
